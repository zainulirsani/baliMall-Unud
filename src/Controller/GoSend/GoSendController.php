<?php


namespace App\Controller\GoSend;


use App\Controller\PublicController;
use App\Entity\Gosend;
use App\Entity\Order;
use App\Service\GoSendService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GoSendController extends PublicController
{
    private $allowedIp = ['34.98.78.50', '192.168.240.1', '35.194.137.234'];

    private $logger;

    public function __construct(TranslatorInterface $translator, ValidatorInterface $validator, LoggerInterface $logger)
    {
        parent::__construct($translator, $validator);

        $this->logger = $logger;
    }

    public function pickupOrder($orderId) {
        $orderRepository = $this->getRepository(Order::class);
        $orderDetail = $orderRepository->find($orderId);
        $gosendService = $this->get(GoSendService::class);
        $gosendRepository = $this->getRepository(Gosend::class);

        $response = [
            'error' => true,
            'message' => null,
            'data' => null
        ];

        if ($orderDetail->getShippingCourier() === 'gosend' && empty($orderDetail->getGosendBookingId())) {
            if ($this->createBookingOrder($orderId)) {

                $gosendDetail = $gosendRepository->findOneBy(['storeOrderId' => $orderDetail->getId()]);

                $response['data'] = [
                    'order_id' => $orderDetail->getId(),
                    'tracking_code' => $gosendDetail->getOrderNo()
                ];

                $response['error'] = false;

                $result = $gosendService->getBookingDetails($gosendDetail->getOrderNo());

                if (!$result['error'] && !empty($result['data'])) {
                    $responseData = $result['data'];

                    $gosendDetail->setStatus($responseData['status']);

                    $em = $this->getEntityManager();
                    $em->persist($gosendDetail);
                    $em->flush();
                }
            }else {
                $response['message'] = 'Error create booking order';
            }
        }

        return $this->view('', $response, 'json');
    }

    public function cancelBookingOrder($orderId)
    {
        $gosendService = $this->get(GoSendService::class);
        $orderRepository = $this->getRepository(Order::class);
        $gosendRepository = $this->getRepository(Gosend::class);

        $order = $orderRepository->find($orderId);
        $gosend = $gosendRepository->find($order->getGosendBookingId());
        $result = $gosendService->cancelBooking($gosend->getOrderNo());

        $response = [
            'error' => true,
            'message' => 'Error, can\'t cancel booking order'
        ];

        if ($result['data']['statusCode'] === 200) {
            $response['error'] = false;
            $response['message'] = null;

            $em = $this->getEntityManager();

            $order->setGosendBookingId(null);

            $em->persist($order);
            $em->flush();

        }elseif ($result['data']['statusCode'] > 300 && !empty($result['data']['message'])) {
            $response['message'] = $result['data']['message'];
        }

        return $this->view('', $response, 'json');
    }

    public function findDriver($orderId)
    {
        $response = [
            'error' => true,
            'message' => 'Error find new driver'
        ];

        if ($this->createBookingOrder($orderId, true)) {
            $response['error'] = false;
            $response['message'] = null;
        }

        return $this->view('', $response, 'json');
    }

    public function createBookingOrder($orderId, $update=false): bool
    {
        $gosendService = $this->get(GoSendService::class);
        $gosendRepository = $this->getRepository(Gosend::class);
        $orderRepository = $this->getRepository(Order::class);
        $orderDetail = $orderRepository->find($orderId);
        $orderProductDetail = $orderDetail->getOrderProducts();
        $storeDetail = $orderDetail->getSeller();
        $sellerDetail = $storeDetail->getUser();
        $buyerDetail = $orderDetail->getBuyer();

        $itemList = [];

        foreach ($orderProductDetail as $item) {
            $itemList[] = $item->getId();
        }

        $totalPrice = $orderDetail->getTotal();

        $itemName = implode(',', $itemList);

        $parameters = [
            'shipment_method' => $orderDetail->getShippingService(),
            'routes' => [
                [
                    'originName' => $sellerDetail->getFirstName() . ' ' . $sellerDetail->getLastName(),
                    'originNote' => '',
                    'originContactName' => $storeDetail->getName(),
                    'originContactPhone' => $sellerDetail->getPhoneNumber(),
                    'originLatLong' => $storeDetail->getAddressLat(). ',' .$storeDetail->getAddressLng(),
                    'originAddress' => $storeDetail->getAddress(),
                    'destinationName' => $buyerDetail->getFirstName(). ' '.$buyerDetail->getLastName(),
                    'destinationNote' => '',
                    'destinationContactName' => $buyerDetail->getFirstName(). ' '.$buyerDetail->getLastName(),
                    'destinationContactPhone' => (string) $buyerDetail->getPhoneNumber(),
                    "destinationLatLong" => $orderDetail->getAddressLat(). ','.$orderDetail->getAddressLng(),
                    "destinationAddress" => $orderDetail->getAddress(),
                    "item" => $itemName,
                    "storeOrderId" => (string) $orderDetail->getId(),
                    "insuranceDetails" => [
                        "applied" => "false",
                        "fee" => "0",
                        "product_description" => $itemName,
                        "product_price" => (string) $totalPrice
                    ]
                ]
            ]
        ];

        $result = $gosendService->createBooking($parameters);

        if (!$result['error'] && !empty($result['data'])) {

            $responseData = $result['data'];

            if ($update) {
                $gosend = $gosendRepository->find($orderDetail->getGosendBookingId());
            }else {
                $gosend = new Gosend();
            }

            $gosend->setBookingId($responseData['id']);
            $gosend->setOrderNo($responseData['orderNo']);
            $gosend->setStoreOrderId($responseData['storeOrderId']);

            $em = $this->getEntityManager();
            $em->persist($gosend);
            $em->flush();

            if(!$update) {
                $orderDetail->setGosendBookingId($gosend->getId());
                $em->persist($orderDetail);
                $em->flush();
            }

            return true;
        }

        return false;
    }

    public function getGosendBookingDetails(string $orderId)
    {
        $user = $this->getUser();

        if ( $user && $user->getRole() !== 'ROLE_USER_SELLER') {
            return $this->redirectToRoute('user_order_index');
        }

        $gosendRepository = $this->getRepository(Gosend::class);
        $orderRepository = $this->getRepository(Order::class);

        $order = $orderRepository->find($orderId);

        if ($user && $user->getId() !== $order->getSeller()->getUser()->getId()) {
            return $this->redirectToRoute('user_order_index');
        }

        $gosendDetail = $gosendRepository->find($order->getGosendBookingId());

        /**
         * @note fetch setiap reload halaman jika webhooks bermasalah
         */

//        $this->fetchBooking($gosendDetail);

        $sellerDetail = $order->getSeller();

        $sellerAddress = [$sellerDetail->getAddressLat(), $sellerDetail->getAddressLng()];
        $buyerAddress = [$order->getAddressLat(), $order->getAddressLng()];
        $gosendStatus = strtolower($gosendDetail->getStatus());
        $enableCancel = $gosendStatus === 'finding driver' || $gosendStatus === 'out_for_pickup';
        $enableFindNewDriver = $gosendStatus === 'driver not found' || $gosendStatus === 'cancelled' || $gosendStatus === 'no_driver' || $gosendStatus === 'rejected';

        return $this->view('@__main__/public/user/order/gosend_booking_detail.html.twig', [
            'order' => $gosendDetail,
            'sellerAddress' => json_encode($sellerAddress),
            'buyerAddress' => json_encode($buyerAddress),
            'enableCancel' => $enableCancel,
            'enableFindNewDriver' => $enableFindNewDriver,
        ]);
    }

    public function gosendWebhooks(): JsonResponse
    {
        $token = getenv('GOSEND_WEBHOOKS_TOKEN');

        $request = $this->getRequest();

        if ($request->getMethod() !== 'POST') {
            $this->logger->error('GoSend-Webhooks wrong http method');
            return new JsonResponse(null,405);
        }

//        if (!in_array($request->getClientIp(), $this->allowedIp) ) {
//            $this->logger->error(sprintf('GoSend-Webhooks IP %s not allowed. Allowed ips = %s' ,$request->getClientIp(), json_encode($this->allowedIp)));
//            return new JsonResponse(null,403);
//        }

        if (!$request->headers->has('Authorization')) {
            $this->logger->error('GoSend-Webhooks empty Auth headers');
            return new JsonResponse(null,401);
        }

        if ($request->headers->get('Authorization') !== $token) {
            $this->logger->error(sprintf('GoSend-Webhooks wrong Auth headers %s ', $request->headers->get('Authorization')));
            return new JsonResponse(null,401);
        }

//        $formData = $request->toArray();

        $formData = json_decode(file_get_contents('php://input'), true);

        $this->logger->error(sprintf('GoSend-Webhooks request payload %s ', json_encode($formData)));

        $gosendRepo = $this->getRepository(Gosend::class);

        try {
            $gosend = $gosendRepo->findOneBy(['orderNo' => $formData['entity_id']]);

            $gosend->setBookingType($formData['booking_type']);
//            $gosend->setDriverId($formData['driver_id']);
            $gosend->setDriverName($formData['driver_name']);
            $gosend->setDriverPhone($formData['driver_phone']);
            $gosend->setDriverPhone2($formData['driver_phone2']);
            $gosend->setDriverPhone3($formData['driver_phone3']);
            $gosend->setDriverPhoto($formData['driver_photo_url']);
            $gosend->setTotalPrice($formData['price']);
            $gosend->setReceiverName($formData['receiver_name']);
            $gosend->setTotalDistance((string) round($formData['total_distance_in_kms'], 2));
            $gosend->setLiveTrackingUrl($formData['live_tracking_url']);
            $gosend->setCancelledBy($formData['cancelled_by']);
            $gosend->setCancelDescription($formData['cancellation_reason']);
            $gosend->setStatus($formData['status']);

            if (!empty($formData['pickup_eta']) && !empty($formData['delivery_eta'])) {
                $pickupEta = explode('-', $formData['pickup_eta']);
                $deliveryEta = explode('-', $formData['delivery_eta']);

                $gosend->setDeliveryEta(sprintf('%s - %s', $this->formatDateTime($deliveryEta[0]), $this->formatDateTime($deliveryEta[1])));
                $gosend->setPickupEta(sprintf('%s - %s', $this->formatDateTime($pickupEta[0]), $this->formatDateTime($pickupEta[1])));
            }

            $em = $this->getEntityManager();
            $em->persist($gosend);
            $em->flush();

            if ($formData['status'] === 'delivered') {
                $orderRepo = $this->getRepository(Order::class);

                $orderDetail = $orderRepo->findOneBy(['gosendBookingId' => $gosend->getId()]);

                $previousOrderValues = clone $orderDetail;

                $orderDetail->setStatus('shipped');

                $em->persist($orderDetail);
                $em->flush();

                $this->logOrder($em, $previousOrderValues, $orderDetail, $orderDetail->getBuyer());
            }

        }catch (\Throwable $throwable) {
            $this->logger->error('GoSend-Webhooks error updating data '.$throwable->getMessage());
        }

        return new JsonResponse(null,200);
    }

    public function formatDateTime(string $datetime):string
    {
        return (string) date('d/m/Y H:i', $datetime);
    }

    public function maskItemName(string $string): string
    {
        $length = strlen($string);
        $visibleCount = (int) round($length / 4);
        $hiddenCount = $length - ($visibleCount * 2);

        return substr($string, 0, $visibleCount) . str_repeat('*', $hiddenCount) . substr($string, ($visibleCount * -1), $visibleCount);
    }

    public function fetchBooking($gosend):void
    {
        if ($gosend instanceof Gosend) {
            $gosendService = $this->get(GoSendService::class);

            $result = $gosendService->getBookingDetails($gosend->getOrderNo());

            $this->logger->error(sprintf('GoSend detail %s', json_encode($result)));

            if (!$result['error']) {
                $responseData = $result['data'];

                if ($responseData['status']) {

                    $gosend->setBookingType($responseData['bookingType']);
                    $gosend->setDriverId($responseData['driverId']);
                    $gosend->setDriverName($responseData['driverName']);
                    $gosend->setDriverPhone($responseData['driverPhone']);
                    $gosend->setDriverPhoto($responseData['driverPhoto']);
                    $gosend->setTotalPrice($responseData['totalPrice']);
                    $gosend->setReceiverName($responseData['receiverName']);
                    $gosend->setOrderCreatedTime($responseData['orderCreatedTime']);
                    $gosend->setOrderDispatchTime($responseData['orderDispatchTime']);
                    $gosend->setOrderArrivalTime($responseData['orderArrivalTime']);
                    $gosend->setSellerAddressName($responseData['sellerAddressName']);
                    $gosend->setSellerAddressDetail($responseData['sellerAddressDetail']);
                    $gosend->setBuyerAddressName($responseData['buyerAddressName']);
                    $gosend->setBuyerAddressDetail($responseData['buyerAddressDetail']);
                    $gosend->setCancelDescription($responseData['cancelDescription']);
                    $gosend->setInsuranceDetails((string) json_encode($responseData['insuranceDetails']));
                    $gosend->setLiveTrackingUrl($responseData['liveTrackingUrl']);
                    $gosend->setStatus($responseData['status']);

                    $em = $this->getEntityManager();
                    $em->persist($gosend);
                    $em->flush();

                    if ($responseData['status'] === 'delivered' || $responseData['status'] === 'Completed') {

                        $orderRepo = $this->getRepository(Order::class);
                        $orderDetail = $orderRepo->findOneBy(['gosendBookingId' => $gosend->getId()]);
                        $previousOrderValues = clone $orderDetail;
                        $orderDetail->setStatus('shipped');

                        $this->logOrder($em, $previousOrderValues, $orderDetail, $orderDetail->getSeller());

                        $em->persist($orderDetail);
                        $em->flush();
                    }
                }
            }
        }
    }
}
