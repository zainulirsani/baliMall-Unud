<?php

namespace App\EventListener;

use App\Controller\PublicController;
use App\Entity\Cart;
use App\Entity\Notification;
use App\Entity\Order;
use App\Entity\OrderPayment;
use App\Entity\Qris;
use App\Entity\Store;
use App\Entity\User;
use App\Entity\VirtualAccount;
use App\Repository\CartRepository;
use App\Repository\OrderPaymentRepository;
use App\Repository\OrderRepository;
use App\Repository\QrisRepository;
use App\Repository\VirtualAccountRepository;
use App\Service\CartService;
use App\Service\QRISClient;
use App\Service\WSClientBPD;
use App\Traits\AppTrait;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class UserLoginListener implements AuthenticationSuccessHandlerInterface
{
    use AppTrait;

    private $manager;
    private $logger;
    private $cart;
    private $b2gSessionKey = 'b2g_cart_session_key';

    public function __construct(EntityManagerInterface $manager, LoggerInterface $logger, array $configuration)
    {
        $this->manager = $manager;
        $this->logger = $logger;
        $this->cart = new CartService($configuration);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();

        $session = $request->getSession();
        $data = $session->all();
        $redirect = $data[getenv('REFERRER_PATH')] ?? '/user/dashboard';
        $roles = ['ROLE_USER', 'ROLE_USER_BUYER', 'ROLE_USER_GOVERNMENT', 'ROLE_USER_BUSINESS'];
        $subRoles = ['PPK','TREASURER'];
        if (in_array($user->getRole(), $roles, false)) {
            if ($user->getRole() == 'ROLE_USER_GOVERNMENT' && in_array($user->getSubRole(), $subRoles, false)) {
                $redirect = '/user/ppk-treasurer/dashboard';
            } else {
                /** @var CartRepository $cartRepository */
                $cartRepository = $this->manager->getRepository(Cart::class);
                /** @var Cart $cart */
                $cart = $cartRepository->findOneBy(['user' => $user]);
                $cartId = $this->cart->getCartId();
                $newCart = isset($data[$cartId]) ? json_decode($data[$cartId], true) : [];
                $oldCart = [];

                if ($cart instanceof Cart) {
                    $oldCart = json_decode($cart->getContent(), true);

                    $this->manager->remove($cart);
                    $this->manager->flush();
                }

                if ($currentCart = array_merge($oldCart, $newCart)) {
                    $session->set($cartId, json_encode($currentCart));

                    $pkpCheck = $this->checkCurrentCartHasPkpItem($currentCart) ? 'pkp' : 'non-pkp';

                    $session->set($this->b2gSessionKey, [
                        'current' => $pkpCheck,
                        'message' => $pkpCheck === 'pkp' ? 'message.info.b2g_cart_is_pkp' : 'message.info.b2g_cart_is_non_pkp',
                    ]);

                    $session->remove(getenv('ORDER_CART_KEY'));
                }

                // Check for payment status via QRIS
                // $this->checkQRISPaymentStatus($user);
                // Check for payment status via Virtual Account
                $this->checkVAPaymentStatus($user);
            }
        }else if ($user->getRole() === 'ROLE_USER_SELLER') {
            $store = $user->getStoreValues();

            if (empty($store) ) {
                $redirect = '/user/store/register';
            }elseif ($store->getStatus() === 'DRAFT' || $store->getStatus() === 'VERIFIED') {
                $redirect = '/user/store';
            }
        }

        return new RedirectResponse($redirect);
    }

    private function checkCurrentCartHasPkpItem(array $cart):bool
    {
        $isPkpItemExist = false;

        foreach ($cart as $items) {
            foreach ($items as $item) {
                if ($item['attributes']['is_pkp'] === 1) {
                 $isPkpItemExist = true;
                }
            }
        }

        return $isPkpItemExist;
    }

    private function checkQRISPaymentStatus(User $user): void
    {
        $qrisClient = new QRISClient();
        /** @var QrisRepository $qrisRepository */
        $qrisRepository = $this->manager->getRepository(Qris::class);
        /** @var Qris[] $payments */
        $payments = $qrisRepository->getUnpaidOrders($user);
        $em = $this->manager;

        foreach ($payments as $qris) {
            try {
                $client = clone $qrisClient;
                $client->setRequestParameters(['qrValue' => $qris->getQrValue()], 'status_check');

                $parameters = $client->getRequestParameters();
                $hashcodeKey = $parameters['hashcodeKey'] ?? '';
                $response = $client->execute();

                if (!$response['error']) {
                    $this->logger->error('QRIS Check Payment Status Response', $response['data']);

                    if (!isset($response['data']['errorCode'])) {
                        $data = $response['data'];
                        $productCode = $data['productCode'] ?? 'NON BILLER';
                        $recordId = $data['recordId'] ?? '';
                        $billNumber = $data['billNumber'] ?? '';
                        $trxId = $data['trxId'] ?? '';
                        $trxDate = $data['trxDate'] ?? '';
                        $trxStatus = $data['trxStatus'] ?? '';
                        //$amount = $data['amount'] ?? '';
                        //$totalAmount = $data['totalAmount'] ?? '';
                        $created = $data['created'] ?? '';
                        $expired = $data['expired'] ?? '';
                        $refundDate = $data['refundDate'] ?? '';
                        $qrId = $data['id'] ?? '';
                        $qrStatus = $data['status'] ?? '';
                        //$qrValue = $data['qrValue'] ?? '';
                        //$merchantName = $data['merchantName'] ?? '';
                        //$merchantPan = $data['merchantPan'] ?? '';
                        //$nmid = $data['nmid'] ?? '';
                        $mid = $data['mid'] ?? '';

                        $status = ucwords($qrStatus);
                        $createdParts = explode(' ', $created);
                        $createdDateParts = explode('/', $createdParts[0]);
                        $createdDate = $createdDateParts[2].'-'.$createdDateParts[1].'-'.$createdDateParts[0].' '.$createdParts[1];

                        $qris->setRecordId((int) $recordId);
                        $qris->setTrxId((int) $trxId);
                        $qris->setTrxDate($trxDate);
                        $qris->setTrxStatus($trxStatus);
                        $qris->setQrId((int) $qrId);
                        $qris->setQrStatus($status);
                        $qris->setProductCode($productCode);
                        $qris->setMid($mid);
                        $qris->setCreatedDate($createdDate);

                        if ($status === 'Sudah Terbayar') {
                            $qris->setTrxStatusDetail($data['responseDescription'] ?? '');
                            $qris->setReferenceNumber($data['referenceNumber'] ?? '');
                            $qris->setResponseCode($data['responseCode'] ?? '');
                        }

                        if ($status === 'Expired' && !empty($expired)) {
                            $expiredParts = explode(' ', $expired);
                            $expiredDateParts = explode('/', $expiredParts[0]);
                            $expiredDate = $expiredDateParts[2].'-'.$expiredDateParts[1].'-'.$expiredDateParts[0].' '.$expiredParts[1];
                            $qris->setExpiredDate($expiredDate);
                        }

                        if (!empty($refundDate) && in_array($trxStatus, ['REFUNDED', 'TO_REFUND'])) {
                            $qris->setRefundDate($refundDate);
                        }

                        $em->persist($qris);
                        $em->flush();

                        if ($status === 'Sudah Terbayar') {
                            $dateParts = explode(' ', $trxDate);
                            $paymentDate = explode('/', $dateParts[0]);

                            /** @var OrderRepository $orderRepository */
                            $orderRepository = $this->manager->getRepository(Order::class);
                            /** @var Order[] $orders */
                            $orders = $orderRepository->findBy(['qrisBillNumber' => $billNumber]);

                            $this->processPayment($orders, [
                                'type' => 'qris',
                                'attachment' => $hashcodeKey,
                                'message' => 'Pembayaran menggunakan QRIS',
                                'date' => sprintf('%s-%s-%s', $paymentDate[2], $paymentDate[1], $paymentDate[0]),
                            ]);
                        }
                    }
                }
            } catch (Exception $e) {
                $this->logger->error(sprintf('QRIS exception handler from %s!', __METHOD__), ['message' => $e->getMessage()]);
            }
        }
    }

    private function checkVAPaymentStatus(User $user): void
    {
        $wsClient = new WSClientBPD();
        /** @var VirtualAccountRepository $repository */
        $repository = $this->manager->getRepository(VirtualAccount::class);
        /** @var VirtualAccount[] $payments */
        $payments = $repository->getUnpaidOrders($user);

        foreach ($payments as $va) {
            try {
                $response = $wsClient->billInquiry($va->getBillNumber());

                $this->logger->error('VA response on payment check after success login!', $response);

                if ($response['status'] && $response['code'] === '00' && $response['data'][0]['sts_bayar'] === '1') {
                    $va->setPaidStatus('1');
                    $va->setResponse(json_encode($response['data']));

                    $this->manager->persist($va);
                    $this->manager->flush();

                    /** @var OrderRepository $orderRepository */
                    $orderRepository = $this->manager->getRepository(Order::class);
                    /** @var Order[] $orders */
                    $orders = $orderRepository->findBy(['sharedInvoice' => $va->getInvoice()]);

                    $this->processPayment($orders, [
                        'type' => 'virtual_account',
                        'attachment' => $va->getReferenceId(),
                        'message' => 'Pembayaran menggunakan Virtual Account',
                        'date' => date('Y-m-d'),
                    ]);
                }
            } catch (Exception $e) {
                $this->logger->error(sprintf('VA exception handler from %s!', __METHOD__), ['message' => $e->getMessage()]);
            }
        }
    }

    private function processPayment(array $orders, array $parameters): void
    {
        /** @var OrderPaymentRepository $paymentRepository */
        $paymentRepository = $this->manager->getRepository(OrderPayment::class);
        $em = $this->manager;

        foreach ($orders as $order) {
            $order->setStatus('paid');
            $order->setStatusChangeTime();

            $this->setDisbursementProductFee($em, $order);

            /** @var Store $store */
            $store = $order->getSeller();
            /** @var User $seller */
            $seller = $store->getUser();
            /** @var User $buyer */
            $buyer = $order->getBuyer();
            /** @var OrderPayment $payment */
            $payment = $paymentRepository->findOneBy(['invoice' => $order->getInvoice()]);

            if (!$payment instanceof OrderPayment) {
                $payment = new OrderPayment();
            }

            $payment->setOrder($order);
            $payment->setInvoice($order->getInvoice());
            $payment->setName($order->getName());
            $payment->setEmail($order->getEmail());
            $payment->setType($parameters['type']);
            $payment->setAttachment($parameters['attachment']);
            $payment->setNominal($order->getTotal() + $order->getShippingPrice());
            $payment->setMessage($parameters['message']);
            $payment->setBankName('bpd_bali');

            try {
                $payment->setDate(new DateTime($parameters['date']));
            } catch (Exception $e) {
            }

            $notification = new Notification();
            $notification->setSellerId($seller->getId());
            $notification->setBuyerId($buyer->getId());
            $notification->setIsSentToSeller(false);
            $notification->setIsSentToBuyer(false);
            $notification->setTitle('Order Status');
            $notification->setContent(sprintf('Order Status No Invoice : %s, %s', $order->getInvoice(), 'paid'));

            $em->persist($order);
            $em->persist($payment);
            $em->persist($notification);
            $em->flush();
        }
    }
}
