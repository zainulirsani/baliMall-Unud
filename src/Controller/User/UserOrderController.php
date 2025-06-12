<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Email\BaseMail;
use App\Entity\Disbursement;
use App\Entity\Doku;
use App\Entity\Satker;
use App\Entity\Bni;
use App\Entity\BniDetail;
use App\Entity\Midtrans;
use App\Entity\Notification;
use App\Entity\Order;
use App\Entity\BpdCc;
use App\Entity\AccessTokenBpd;
use App\Entity\OrderComplaint;
use App\Entity\OrderNegotiation;
use App\Entity\OrderPayment;
use App\Entity\OrderProduct;
use App\Entity\OrderShippedFile;
use App\Entity\Product;
use App\Entity\ProductReview;
use App\Entity\Qris;
use App\Entity\Store;
use App\Entity\User;
use App\Entity\UserBpdBinding;
use App\Entity\BpdRequestBinding;
use App\Entity\DocumentApproval;
use App\Entity\VoucherUsedLog;
use App\Entity\VirtualAccount;
use App\EventListener\RemoveOrderPaymentEntityListener;
use App\Helper\StaticHelper;
use App\Repository\BpdCcRepository;
use App\Repository\AccessTokenBpdRepository;
use App\Repository\DokuRepository;
use App\Repository\OrderPaymentRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductReviewRepository;
use App\Repository\StoreRepository;
use App\Repository\VoucherUsedLogRepository;
use App\Repository\QrisRepository;
use App\Repository\VirtualAccountRepository;
use App\Service\BreadcrumbService;
use App\Service\BpdSnapService;
use App\Service\DokuService;
use App\Service\BniService;
use App\Service\FileUploader;
use App\Service\MidtransService;
use App\Service\QrCodeGenerator;
use App\Service\QRISClient;
use App\Service\WSClientBPD;
use App\Utility\CustomPaginationTemplate;
use App\Service\HttpClientService;
use App\Entity\UserPpkTreasurer;
use App\Entity\UserPicDocument;
use App\Service\SftpUploader;
use DateTime;
use Carbon\Carbon;
use DateTimeZone;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use Exception;
use finfo;
use iio\libmergepdf\Driver\TcpdiDriver;
use iio\libmergepdf\Merger;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints;
use NumberFormatter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

class UserOrderController extends PublicController
{
    private $buyerOrigin = '4b5771';
    private $sellerOrigin = '48fcb8';
    private $allowedRoles = ['ROLE_USER', 'ROLE_USER_BUYER', 'ROLE_USER_GOVERNMENT', 'ROLE_USER_BUSINESS'];
    protected $logger;

    public function __construct(TranslatorInterface $translator, ValidatorInterface $validator, LoggerInterface $logger, ?SftpUploader $sftpUploader=null )
    {
        parent::__construct($translator, $validator, $sftpUploader);

        $this->logger = $logger;
    }

    public function index()
    {
        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        /** @var User $user */
        $user = $this->getUser();

        if($user->getRoles()[0] != "ROLE_USER_SELLER") {
            return $this->redirectToRoute('login');
        }

        $request = $this->getRequest();
        $page = abs($request->query->get('page', '1'));
        $keywords = $request->query->get('keywords', null);
        $status = $request->query->get('status', null);
        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $template = '@__main__/public/user/order/index.html.twig';
        $parameters = [
            'limit' => $limit,
            'offset' => $offset,
            'status' => $status,
            'order_by' => 'o.id',
            'sort_by' => 'DESC',
        ];

        $orderStatuses = $this->getParameter('order_statuses');

        if ($this->getUser() && $this->getUser()->getRole() === 'ROLE_USER') {
            $orderStatuses = array_filter($orderStatuses, function ($k) {
                return !in_array($k, ['pending_payment', 'payment_process', 'tax_invoice', 'document']);
            }, ARRAY_FILTER_USE_KEY);
        }

        if ($this->getUserStore()) {
            $parameters['seller'] = $this->getUserStore();
            $parameters['exclude_status'] = ['pending'];
        } else {
            $template = '@__main__/public/user/order/index_v2.html.twig';
            $parameters['version'] = 'v2';
            $parameters['buyer'] = $user;
        }

        if (!empty($keywords)) {
            $parameters['keywords'] = filter_var($keywords, FILTER_SANITIZE_STRING);
        } else {
            unset($parameters['keywords']);
        }

        if (!empty($status) && in_array($status, array_keys($orderStatuses))) {
            $parameters['status'] = filter_var($status, FILTER_SANITIZE_STRING);
        } else {
            unset($parameters['status']);
        }

        try {
            $adapter = new DoctrineORMAdapter($repository->getPaginatedResult($parameters));
            $pagination = new Pagerfanta($adapter);
            $pagination
                ->setMaxPerPage($limit)
                ->setCurrentPage($page);

            $view = new DefaultView(new CustomPaginationTemplate());
            $options = ['proximity' => 3];
            $html = $view->render($pagination, $this->routeGenerator($parameters), $options);

            $orders = $adapter->getQuery()->getScalarResult();
            foreach ($orders as &$order) {
                $order['o_products'] = $repository->getOrderProducts($order['o_id']);
                $order['o_negotiatedProducts'] = $repository->getOrderNegotiationProducts($order['o_id']);
            }
            unset($order);
        } catch (Exception $e) {
            $orders = [];
            $pagination = $html = null;
        }

        BreadcrumbService::add(['label' => $this->getTranslation('label.order_history')]);

        return $this->view($template, [
            'orders' => $orders,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
            'filter_keywords' => $keywords,
            'filter_status' => $status,
            'order_statuses' => $orderStatuses,
        ]);
    }

    public function review()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $origin = $request->request->get('origin', 'invalid');
        $response = ['status' => false];
        
        
        if ($origin === $this->buyerOrigin) {
            $orderId = abs($request->request->get('oid', '0'));
            $productId = abs($request->request->get('pid', '0'));
            /** @var OrderRepository $repository */
            $orderRepository = $this->getRepository(Order::class);
            $productReviewRepository = $this->getRepository(ProductReview::class);
            /** @var Order $order */
            $order = $orderRepository->find($orderId);
            
            if ($order instanceof Order) {
                /** @var OrderProduct $orderProduct */
                $orderProduct = $orderRepository->getSingleOrderProduct($orderId, $productId);
                /** @var Product $product */
                $product = $orderProduct->getProduct();
                
                if ($product instanceof Product) {
                    $review = $request->request->get('review', null);
                    $rating = abs($request->request->get('rating', '0'));
                    $attachment = $request->request->get('attachment', null);
                    /** @var User $buyer */
                    $buyer = $this->getUser();
                    /** @var ProductReviewRepository $repository */
                    $repository = $this->getRepository(ProductReview::class);
                    /** @var ProductReview $productReview */
                    $productReview = $repository->findOneBy([
                        'order' => $order,
                        'user' => $buyer,
                        'product' => $product,
                    ]);

                    // dd([
                    //     // 'order' => $order,
                    //     'user' => $buyer,
                    //     'product' => $product,]);

                    // dd($productReview);

                    if (!$productReview instanceof ProductReview) {
                        $productReview = new ProductReview();
                        $productReview->setOrder($order);
                        $productReview->setUser($buyer);
                        $productReview->setProduct($product);
                        $productReview->setRating($rating);
                        $productReview->setStatus('publish');

                        if (!empty($review)) {
                            $productReview->setReview(filter_var($review, FILTER_SANITIZE_STRING));
                        }

                        if (!empty($attachment)) {
                            $productReview->setAttachment(ltrim($attachment, '/'));
                        }
                        
                        $validator = $this->getValidator();
                        $productReviewErrors = $validator->validate($productReview);
                        
                        if ($orderProduct !== false && count($productReviewErrors) === 0) {
                            $template = '@__main__/public/user/order/fragments/product_rating_detail.html.twig';

                            $em = $this->getEntityManager();
                            $em->persist($productReview);
                            $em->flush();
                            
                            $response['status'] = true;
                            $response['content'] = $this->renderView($template, [
                                'review' => $productReview->getReview(),
                                'rating' => $productReview->getRating(),
                            ]);

                            $all_rating = true;
                            $op_all = $orderRepository->getOrderProducts($orderId);
                            foreach ($op_all as $key => $value) {
                                $data_review = $productReviewRepository->getProductReviewDetail($orderId, $value['p_id'], $order->getPpkId());
                                if (count($data_review) == 0) {
                                    $all_rating = false;
                                }
                            }
                            
                            if ($all_rating == true) {
                                if ($order->getStatus() == 'shipped') {
                                    $status = 'received';
                                    $prevOrderValues = clone $order;
                                    $order->setStatusRating('all');
                                    $order->setStatus($status);
                                    $this->logOrder($this->getEntityManager(), $prevOrderValues, $order, $this->getUser());
                                    $status = 'pending_payment';
                                    $this->setOrderStatus($order, $status);
                                }
                            }
                        } else {
                            $errors = [];
                            
                            foreach ($productReviewErrors as $error) {
                                $errors[$error->getPropertyPath()] = $error->getMessage();
                            }

                            $response['errors'] = $errors;
                        }
                    }
                }
            }
        }
        
        return $this->view('', $response, 'json');
    }

    public function publishReview()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $origin = $request->request->get('origin', 'invalid');
        $response = ['status' => false];

        if ($origin === $this->sellerOrigin) {
            $reviewId = abs($request->request->get('id', '0'));
            $orderId = abs($request->request->get('oid', '0'));
            $productId = abs($request->request->get('pid', '0'));
            /** @var OrderRepository $repository */
            $repository = $this->getRepository(Order::class);
            /** @var Order $order */
            $order = $repository->find($orderId);

            if ($order instanceof Order) {
                /** @var OrderProduct $orderProduct */
                $orderProduct = $repository->getSingleOrderProduct($orderId, $productId);

                if ($orderProduct !== false) {
                    /** @var Product $product */
                    $product = $orderProduct->getProduct();
                    /** @var ProductReviewRepository $repository */
                    $repository = $this->getRepository(ProductReview::class);
                    /** @var ProductReview $productReview */
                    $productReview = $repository->findOneBy([
                        'id' => $reviewId,
                        'order' => $order,
                        'product' => $product,
                        'status' => 'draft',
                    ]);

                    if ($productReview instanceof ProductReview) {
                        if ($product->getReviewTotal() === 0) {
                            /** @var ProductRepository $repository */
                            $repository = $this->getRepository(Product::class);
                            $total = $repository->getTotalProductReviewByProduct($product->getId());

                            $product->setReviewTotal((int)$total['count']);
                        }

                        $product->sumRatingCount($productReview->getRating());
                        $product->incrementReviewTotal();
                        $productReview->setStatus('publish');

                        $em = $this->getEntityManager();
                        $em->persist($product);
                        $em->persist($productReview);
                        $em->flush();

                        $response['status'] = true;
                    }
                }
            }
        }

        return $this->view('', $response, 'json');
    }

    public function deletePayment()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $origin = $request->request->get('origin', 'invalid');
        $response = ['deleted' => false];

        if ($origin === $this->sellerOrigin) {
            $orderId = abs($request->request->get('oid', '0'));
            $paymentId = abs($request->request->get('pid', '0'));
            /** @var OrderRepository $repository */
            $repository = $this->getRepository(Order::class);
            /** @var Order $order */
            $order = $repository->find($orderId);

            if ($order instanceof Order) {
                /** @var OrderPaymentRepository $repository */
                $repository = $this->getRepository(OrderPayment::class);
                /** @var OrderPayment $orderPayment */
                $orderPayment = $repository->findOneBy([
                    'id' => $paymentId,
                    'order' => $order,
                ]);

                if ($orderPayment instanceof OrderPayment) {
                    $this->appGenericEventDispatcher(new GenericEvent($orderPayment, [
                        'em' => $this->getEntityManager(),
                    ]), 'front.delete_order_payment', new RemoveOrderPaymentEntityListener());

                    $response['deleted'] = true;
                }
            }
        }

        return $this->view('', $response, 'json');
    }

    public function shared(LoggerInterface $logger, $id)
    {
        $this->deniedManyAccess($this->allowedRoles);

        $parts = explode(':', base64_decode($id));

        $sharedInvoice = $parts[1] ?? 'n/a';

        $isAbleToCancelOrder = false;

        if ($sharedInvoice === 'n/a') {
            throw new NotFoundHttpException('Invalid order shared invoice!');
        }

        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        /** @var User $buyer */
        $buyer = $this->getUser();
        $parameters = [
            'buyer' => $buyer,
        ];

        /** @var VirtualAccountRepository $vaRepository */
        $vaRepository = $this->getRepository(VirtualAccount::class);
        /** @var VirtualAccount $va */
        $va = $vaRepository->findOneBy([
            'invoice' => $sharedInvoice,
            'paidStatus' => '0',
        ]);

        if ($va instanceof VirtualAccount) {
            $wsClient = new WSClientBPD();

            try {
                $response = $wsClient->billInquiry($va->getBillNumber());

                $logger->error('VA response on shared invoice page!', $response);

                if ($response['status'] && $response['code'] === '00' && $response['data'][0]['sts_bayar'] === '1') {
                    $va->setPaidStatus('1');
                    $va->setResponse(json_encode($response['data']));

                    $em = $this->getEntityManager();
                    $em->persist($va);
                    $em->flush();

                    $tempOrders = $repository->findBy(['sharedInvoice' => $sharedInvoice]);
                    /** @var OrderPaymentRepository $paymentRepository */
                    $paymentRepository = $this->getRepository(OrderPayment::class);
                    $translator = $this->getTranslator();

                    foreach ($tempOrders as $tempOrder) {
                        /** @var Store $store */
                        $store = $tempOrder->getSeller();
                        /** @var User $seller */
                        $seller = $store->getUser();
                        /** @var User $buyer */
                        $buyer = $tempOrder->getBuyer();

//                        $tempOrder->setStatus($tempOrder->getIsB2gTransaction() ? 'payment_process' : 'paid');
                        $tempOrder->setStatus($tempOrder->getIsB2gTransaction() ? 'paid' : 'confirmed');

                        /** @var OrderPayment $payment */
                        $payment = $paymentRepository->findOneBy(['invoice' => $tempOrder->getInvoice()]);

                        if (!$payment instanceof OrderPayment) {
                            $payment = new OrderPayment();
                        }

                        $payment->setOrder($tempOrder);
                        $payment->setInvoice($tempOrder->getInvoice());
                        $payment->setName($tempOrder->getName());
                        $payment->setEmail($tempOrder->getEmail());
                        $payment->setType('virtual_account');
                        $payment->setAttachment($va->getReferenceId());
                        $payment->setNominal($tempOrder->getTotal() + $tempOrder->getShippingPrice());
                        $payment->setMessage('Pembayaran menggunakan Virtual Account');
                        $payment->setBankName('bpd_bali');

                        try {
                            $payment->setDate(new DateTime('now'));
                        } catch (Exception $e) {
                        }

                        $notification = new Notification();
                        $notification->setSellerId($seller->getId());
                        $notification->setBuyerId($buyer->getId());
                        $notification->setIsSentToSeller(false);
                        $notification->setIsSentToBuyer(false);
                        $notification->setTitle($translator->trans('notifications.order_status'));
                        $notification->setContent($translator->trans('notifications.order_status_text', ['%invoice%' => $tempOrder->getInvoice(), '%status%' => 'paid']));

                        $em->persist($tempOrder);
                        $em->persist($payment);
                        $em->persist($notification);
                        $em->flush();
                    }
                }
            } catch (Exception $e) {
            }
        }

        $orders = $repository->getOrderDetailBySharedInvoice($sharedInvoice, $parameters);

        $sharedId = $orders[0]['o_sharedId'];
        $isPkpOrder = false;

        foreach ($orders as $order) {
            if ($order['s_pkp'] === '1') {
                $isPkpOrder = true;
            }

            if (empty($order['o_cancellationStatus']) && $order['o_status'] === 'processed') {
                $isAbleToCancelOrder = true;
            }
        }

//        if ($buyer && $buyer->getLkppRole() !== 'PPK') {
//            $isAbleToCancelOrder = false;
//        }

        BreadcrumbService::add(
            ['label' => $this->getTranslation('label.order_history'), 'href' => $this->get('router')->generate('user_order_index')],
            ['label' => sprintf('%s %s', $this->getTranslation('title.page.order'), $sharedInvoice)]
        );

        $orderRepository = $this->getRepository(Order::class);
        $ordersBySharedId = $orderRepository->findBy(['sharedInvoice' => $sharedInvoice]);
        $nominal = $this->getTotalToBePaidForPayment($sharedInvoice, $ordersBySharedId);
        $minimumAmountForDoku = $this->getParameter('dokuMinimumTransactionAmount');
        $isB2gTransaction = $orderRepository->findOneBy(['sharedInvoice' => $sharedInvoice])->getIsB2gTransaction() ?? false;

        $dokuPaymentUrl = null;
        $isDokuEnable = $isB2gTransaction === true;
        $isMidtransEnable = $isB2gTransaction === false && $this->getParameter('is_midtrans_enable');

        if ($nominal < $minimumAmountForDoku) {
            $isDokuEnable = false;
        }

        if (!empty($orders[0]['o_doku_id']) && $isDokuEnable) {
            $dokuRepository = $this->getRepository(Doku::class);

            $dokuDetail = $dokuRepository->findOneBy(['id' => $orders[0]['o_doku_id'], 'status' => 'PENDING']);

            if (!empty($dokuPaymentUrl)) {
                $dokuPaymentUrl = $dokuDetail->getUrl();
            }
        }

        return $this->view('@__main__/public/user/order/shared.html.twig', [
            'id' => $id,
            'shared_id' => $sharedId,
            'orders' => $orders,
            'is_pkp_order' => $isPkpOrder,
            'user_type' => 'buyer',
            'buyer' => $buyer,
            'doku_payment_url' => $dokuPaymentUrl,
            'is_doku_enable' => $isDokuEnable,
            'is_midtrans_enable' => $isMidtransEnable,
            'is_able_to_cancel_order' => $isAbleToCancelOrder,
        ]);
    }

    public function printSharedInvoice($id): Response
    {
        $this->deniedManyAccess($this->allowedRoles);

        $parts = explode(':', base64_decode($id));
        $sharedInvoice = $parts[1] ?? 'n/a';

        if ($sharedInvoice === 'n/a') {
            throw new NotFoundHttpException('Invalid order shared invoice!');
        }

        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);


        /** @var User $buyer */
        $buyer = $this->getUser();
        $parameters = [
            'buyer' => $buyer,
        ];

        $sharedId = $repository->getOrderDetailBySharedInvoice($sharedInvoice, $parameters)[0]['o_sharedId'];

        /** @var VoucherUsedLogRepository $voucherRepository */
        $voucherRepository = $this->getRepository(VoucherUsedLog::class);
        $data = [
            'orders' => $repository->getOrderDetailBySharedInvoice($sharedInvoice, $parameters),
        ];

        return $this->handlePrint('shared_invoice', $data, ['stream' => true]);
    }

    // Check if order has not been paid before requesting data to QRIS / VA
    public function payWithChannel(LoggerInterface $logger, $channel)
    {
        $sharedInvoice = $this->getRequest()->query->get('id', '');
        $invoice = $this->getRequest()->query->get('invoice', '');
        if (
            empty($sharedInvoice) ||
            !in_array($channel, ['qris', 'virtual-account', 'doku', 'midtrans', 'artpay'])
        ) {
            return $this->redirectToRoute('user_order_index');
        }

        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->getRepository(Order::class);
        /** @var Order[] $orders */
        $orders = $orderRepository->findBy([
            'sharedId' => $sharedInvoice,
            'invoice' => $invoice,
            'status' => 'pending',
        ]);

        // Pengecekan khusus B2G
        if (count($orders) < 1) {
            /** @var Order[] $orders */
            $orders = $orderRepository->findBy([
                'sharedId' => $sharedInvoice,
                'invoice' => $invoice,
            ]);
        }
        // Calculate the amount of the order(s)
        $nominal = $this->getTotalToBePaidForPayment($sharedInvoice, $orders);

        if ($nominal == 0 || count($orders) < 1) {
            return $this->redirectToRoute('user_order_index');
        }

        $isB2g = $orders[0]->getIsB2gTransaction() ?? false;

        if ($isB2g && !in_array($channel, ['qris', 'virtual-account', 'doku', 'artpay'])) {
            return $this->redirectToRoute('user_order_index');
        }

        if (!$isB2g && !in_array($channel, ['qris', 'virtual-account', 'midtrans'])) {
            return $this->redirectToRoute('user_order_index');
        }


        $billNumber = (string)$orders[0]->getQRISBillNumber(); // QRIS
        $invoice = (string)$orders[0]->getSharedInvoice(); // VA
        $isValidCustomerOrder = ($orders[0]->getIsB2gTransaction() === false && $orders[0]->getStatus() === 'pending');
        $isValidB2GOrder = ($orders[0]->getIsB2gTransaction() === true);
        $QRISPaymentData = null;
        $VAPaymentData = null;

        if ($channel === 'qris') {
            /** @var QrisRepository $qrisRepository */
            $qrisRepository = $this->getRepository(Qris::class);
            $qris = $qrisRepository->findOneBy(['invoice' => $billNumber]);

            if ($qris) {
                // Note @2021-04-21: always hit API even if the data exists in database and is not expired -- part of UAT
                /*if (strtotime($qris->getExpiredDate()) < time()) {
                    $QRISPaymentData = $this->generateQRISPayment($billNumber, (string) $qris->getTotalAmount(), $logger, $qris);
                } else {
                    $QRISPaymentData = [
                        'qrImage' => $qris->getQrImage(),
                        'expiredDate' => $qris->getExpiredDate(),
                        'billNumber' => $qris->getBillNumber(),
                    ];
                }*/

                if ($qris->getQrStatus() === 'Expired') {
                    $qris = null;
                }

                $QRISPaymentData = $this->generateQRISPayment($billNumber, (string)$qris->getTotalAmount(), $logger, $qris);
            } else {
                // If order amount is below the limit, generate qrcode for payment with QRIS
                $amountLimit = $this->getParameter('qris_amount_limit');

                if ($nominal <= $amountLimit) {
                    if (empty($billNumber)) {
                        do {
                            // TODO: in the future change max parameter into 10 or more digit
                            $billNumber = (string)StaticHelper::generateInt(999, 999999999);
                            $count = $qrisRepository->count(['invoice' => $billNumber]);
                            $found = $count === 0 ? 'yes' : 'no';
                        } while ($found === 'no');

                        /** @var Order[] $tempOrders */
                        $tempOrders = $orderRepository->findBy([
                            'sharedId' => $sharedInvoice,
                        ]);

                        if (count($tempOrders) > 0) {
                            $em = $this->getEntityManager();

                            // After getting bill_number value, update related order(s)
                            foreach ($tempOrders as $tempOrder) {
                                $tempOrder->setQRISBillNumber($billNumber);
                                $em->persist($tempOrder);
                            }

                            $em->flush();
                        }
                    }

                    $QRISPaymentData = $this->generateQRISPayment($billNumber, (string)$nominal, $logger);
                }
            }
        }

        if (($isValidCustomerOrder || $isValidB2GOrder) && $channel === 'virtual-account') {
            /** @var VirtualAccountRepository $vaRepository */
            $vaRepository = $this->getRepository(VirtualAccount::class);
            /** @var VirtualAccount $va */
            $va = $vaRepository->findOneBy(['invoice' => $invoice]);

            if ($va instanceof VirtualAccount) {
                if ($va->getPaidStatus() === '99') {
                    $VAPaymentData = $this->generateVAPayment($va, $logger);
                } else {
                    $VAPaymentData = !empty($va->getResponse()) ? json_decode($va->getResponse(), true) : null;
                }
            } else {
                /** @var User $buyer */
                $buyer = $this->getUser();
                $em = $this->getEntityManager();
                $ids = [];

                foreach ($orders as $key => $order) {
                    $ids[] = $order->getId();
                }

                $va = new VirtualAccount();
                $va->setInvoice($orders[0]->getSharedInvoice());
                $va->setTransactionId(implode('|', $ids));
                $va->setReferenceId(Uuid::uuid4()->toString());
                $va->setName(trim($buyer->getFirstName() . ' ' . $buyer->getLastName()));
                $va->setAmount($nominal);
                $va->setInstitution(getenv('WS_BPD_INSTITUTION'));

                // Save first to get the ID
                $em->persist($va);
                $em->flush();

                // INFO: max length is 10 digit
                $va->setBillNumber(substr('0000000000' . $va->getId(), -10));
                $em->persist($va);
                $em->flush();

                $VAPaymentData = $this->generateVAPayment($va, $logger);
            }
        }

        if (($isValidCustomerOrder || $isValidB2GOrder) && $channel === 'doku') {

            $minimunAmount = $this->getParameter('dokuMinimumTransactionAmount');
            if ($nominal >= $minimunAmount) {
                if (!empty($orders[0]->getDokuInvoiceNumber())) {

                    /** @var DokuRepository $dokuRepository */
                    $dokuRepository = $this->getRepository(Doku::class);

                    $dokuDetail = $dokuRepository->findOneBy(['invoice_number' => $orders[0]->getDokuInvoiceNumber()]);

                    if ($dokuDetail) {
                        return $this->redirect($dokuDetail->getUrl());
                    }
                } else {
                    $dokuService = $this->get(DokuService::class);
                    $dokuInvoiceNumber = $this->generateDokuInvoiceNumber();
                    $requestId = $this->generateRequestId();
                    if (strpos($orders[0]->getEmail(), '+++') !== false || strpos($orders[0]->getPpkEmail(), '+++') !== false || strpos($orders[0]->getTreasurerEmail(), '+++') !== false) {
                        $emailParts = explode('+++', $orders[0]->getEmail());
                        $emailPartsPPK = explode('+++', $orders[0]->getPpkEmail());
                        $emailTreasurer = explode('+++', $orders[0]->getTreasurerEmail());
                        $orders[0]->setEmail($emailParts[0]);
                        $orders[0]->setPpkEmail($emailPartsPPK[0]);
                        $orders[0]->setTreasurerEmail($emailTreasurer[0]);
                        // dd($orders[0]->getEmail(), $orders[0]->getPpkEmail(), $orders[0]->getTreasurerEmail());
                    }
                    // dd($requestId, $dokuInvoiceNumber, $orders, $nominal);
                    $result = $dokuService->requestPayment($dokuInvoiceNumber, $requestId, $orders, $nominal);

                    if ($result['status']) {
                        $doku = new Doku();
                        $requestPaymentStatus = $result['data']['message'][0] === 'SUCCESS' ? 'PENDING' : null;
                        $responseData = $result['data']['response'];
                        $dokuInvoiceNumber = $responseData['order']['invoice_number'];

                        $doku->setStatus($requestPaymentStatus);
                        $doku->setAmount($responseData['order']['amount']);
                        $doku->setExpiredDate(new DateTime($responseData['payment']['expired_date']));
                        $doku->setInvoiceNumber($dokuInvoiceNumber);
                        $doku->setTokenId($responseData['payment']['token_id']);
                        $doku->setUrl($responseData['payment']['url']);
                        $doku->setUuid($responseData['uuid']);
                        $doku->setSharedInvoice($orders[0]->getSharedInvoice());

                        $em = $this->getEntityManager();
                        $em->persist($doku);
                        $em->flush();

                        foreach ($orders as $order) {
                            $order->setDokuInvoiceNumber($dokuInvoiceNumber);
                            $em->persist($order);
                            $em->flush();
                        }

                        return $this->redirect($responseData['payment']['url']);
                    }
                }
            }
        }

        if (($isValidCustomerOrder || $isValidB2GOrder) && $channel === 'artpay') {

            $minimunAmount = $this->getParameter('dokuMinimumTransactionAmount');

            if ($nominal >= $minimunAmount) {
                if (!empty($orders[0]->getDokuInvoiceNumber())) {
                    /** @var DokuRepository $dokuRepository */
                    $dokuRepository = $this->getRepository(Doku::class);

                    $dokuDetail = $dokuRepository->findOneBy(['invoice_number' => $orders[0]->getDokuInvoiceNumber()]);

                    if ($dokuDetail) {
                        return $this->redirect($dokuDetail->getUrl());
                    }
                } else {
                    $dokuService = $this->get(DokuService::class);
                    $dokuInvoiceNumber = $this->generateArtpayInvoiceNumber();
                    $requestId = $this->generateRequestId();
                    // dd($orders[0]->getOrderProducts());
                    $result = $dokuService->requestPaymentArtpay($dokuInvoiceNumber, $requestId, $orders, $nominal);

                    if ($result['status']) {
                        $doku = new Doku();
                        $requestPaymentStatus = $result['data']['message'][0] === 'SUCCESS' ? 'PENDING' : null;
                        $responseData = $result['data']['data'][0];
                        $dokuInvoiceNumber = $responseData['order']['invoice_number'];

                        $doku->setStatus($requestPaymentStatus);
                        $doku->setAmount($responseData['order']['amount']);
                        $doku->setExpiredDate(new DateTime($responseData['payment']['expired_date']));
                        $doku->setInvoiceNumber($dokuInvoiceNumber);
                        $doku->setTokenId($responseData['payment']['token_id']);
                        $doku->setUrl($responseData['payment']['url']);
                        $doku->setUuid($responseData['uuid']);
                        $doku->setSharedInvoice($orders[0]->getSharedInvoice());

                        $em = $this->getEntityManager();
                        $em->persist($doku);
                        $em->flush();

                        foreach ($orders as $order) {
                            $order->setDokuInvoiceNumber($dokuInvoiceNumber);
                            $em->persist($order);
                            $em->flush();
                        }

                        return $this->redirect($responseData['payment']['url']);
                    }
                }
            }
        }

        if ($channel === 'midtrans' && $this->getParameter('is_midtrans_enable')) {
            $midtransPaymentData = null;
            $snapToken = null;
            $redirectUrl = '/user/order/shared/' . base64_encode('bm-order:' . $orders[0]->getSharedInvoice());

            if (empty($orders[0]->getMidtransId())) {
                $midtransService = $this->get(MidtransService::class);

                $orderId = $this->generateMidtransOrderId();
                $result = $midtransService->requestPayment($nominal, $orders, $orderId);

                if ($result['status']) {
                    $snapToken = $result['data']['token'];

                    $midtrans = new Midtrans();
                    $midtrans->setStatus('pending');
                    $midtrans->setSharedInvoice($orders[0]->getSharedInvoice());
                    $midtrans->setToken($snapToken);
                    $midtrans->setOrderId($orderId);

                    $em = $this->getEntityManager();
                    $em->persist($midtrans);
                    $em->flush();

                    foreach ($orders as $order) {
                        $order->setMidtransId($midtrans->getId());
                        $em->persist($order);
                    }

                    $em->flush();
                }
            } else {
                $midtransRepository = $this->getRepository(Midtrans::class);

                $midtrans = $midtransRepository->findOneBy(['sharedInvoice' => $orders[0]->getSharedInvoice()]);

                $snapToken = $midtrans->getToken();
            }

            $midtransPaymentData = [
                'token' => $snapToken,
                'redirect_url' => $redirectUrl,
            ];

            return $this->view('@__main__/public/order/pay.html.twig', [
                'channel' => $channel,
                'shared_id' => $sharedInvoice,
                'midtrans_payment_data' => $midtransPaymentData
            ]);
        }

        return $this->view('@__main__/public/order/pay.html.twig', [
            'channel' => $channel,
            'nominal' => $nominal,
            'qris_payment_data' => $QRISPaymentData,
            'va_payment_data' => $VAPaymentData,
            'shared_id' => $sharedInvoice,
            'order_id' => $orders[0]->getId()
        ]);
    }

    public function detail($id)
    {
        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        $flashBag = $this->get('session.flash_bag');
        $parameters = [];
        $isAbleToCancelOrder = false;

        $this->getDefaultData();

        if ($this->getUserStore()) {
            $userType = 'seller';
            /** @var Store $seller */
            $seller = $this->getUserStore();
            $parameters['seller'] = $seller;
        } else {
            $userType = 'buyer';
            /** @var User $buyer */
            $buyer = $this->getUser();
            $parameters['buyer'] = $buyer;
        }

        $order = $repository->getOrderDetail($id, $parameters);

        if (!empty($order)) {
            if (
                $order['o_cancellationStatus'] === 'requested' &&
                $order['o_status'] === 'processed' &&
                $order['o_negotiationStatus'] === 'finish' &&
                $userType === 'seller'
            ) {
                $isAbleToCancelOrder = true;
            }
        }

        BreadcrumbService::add(
            ['label' => $this->getTranslation('label.order_history'), 'href' => $this->get('router')->generate('user_order_index')],
            ['label' => sprintf('%s %s', $this->getTranslation('title.page.order'), $order['o_invoice'])]
        );

        $reduceOrderByVoucher = [];

        if (!empty($order['o_sharedId'])) {
            $reduceOrderByVoucher = $this->getVoucherListForEachOrder($order['o_sharedId'], $order['s_pkp']);
        }

        $disbursementRepository = $this->getRepository(Disbursement::class);
        $disbursementData = null;

        if ($this->getUser() && $this->getUser()->getRole() === 'ROLE_USER_SELLER') {
            try {
                $disbursementData = $disbursementRepository->findOneBy(['orderId' => $id]);
            } catch (\Throwable $throwable) {
                $disbursementData = null;
            }
        }


        // dd($order, $userType, $reduceOrderByVoucher, $disbursementData);
        return $this->view('@__main__/public/user/order/detail.html.twig', [
            'order' => $order,
            'user_type' => $userType,
            'colorbox' => true,
            'errors' => $flashBag->get('errors'),
            'reduce_order_by_id' => $reduceOrderByVoucher,
            'disbursement_data' => $disbursementData,
            'is_able_to_cancel_order' => $isAbleToCancelOrder,
        ]);
    }

    public function update($id)
    {
        $this->isAjaxRequest('POST');

        $this->denyAccessUnlessGranted('order.cancel_order' , 'permission');
        $this->denyAccessUnlessGranted((int) $id , 'order_permission');    
            

        $request = $this->getRequest();
        $documentApprovalRepository = $this->getRepository(DocumentApproval::class);
        $user = $this->getUser();

        $state = $request->request->get('state', 'invalid');
        $origin = $request->request->get('origin', 'invalid');
        $userType = $request->request->get('user_type', 'invalid');
        $reasonCancel = $request->request->get('reason', null);
        $waybill = $request->request->get('waybill', null);
        $deliveryQty = $request->request->get('delivery_qty', null);
        $deliveryPid = $request->request->get('delivery_pid', null);
        if ($state == 'shipped') {
            $shipped_method = $request->request->get('shipped-method', null);
            $self_courier_name = $request->request->get('self-courier-name', null);
            $self_courier_position = $request->request->get('self-courier-position', null);
            // $self_courier_address = $request->request->get('self-courier-address', null);
            $self_courier_telp = $request->request->get('self-courier-telp', null);
            $shipped_product_img = $request->files->get('shipped-product-img', null);
            $state_img = $request->files->get('state-img', null);
        }
        $response = [
            'status' => false,
        ];

        if ($id > 0 && in_array($origin, [$this->buyerOrigin, $this->sellerOrigin], false)) {
            /** @var OrderRepository $repository */
            $repository = $this->getRepository(Order::class);
            /** @var Order $order */
            $order = $repository->find($id);
            $parameters = [
                'status' => $state,
                'user_type' => $userType,
                'reason_cancel' => $reasonCancel
            ];

            if ($state === 'shipped') {
                // if (empty($waybill) && $order->getShippingCourier() !== 'free_delivery') {
                //     $parameters['message'] = $this->getTranslation('not_empty', [], 'validators');

                //     return $this->view('', $response, 'json');
                // }

                if (isset($deliveryPid) && isset($deliveryQty)) {
                    $parameters['delivery_qty'] = explode('|', $deliveryQty);
                    $parameters['delivery_pid'] = explode('|', $deliveryPid);
                }

                $parameters['waybill'] = $waybill;
                if ($state == 'shipped') {
                    $parameters['shipped_method'] = $shipped_method;
                    $parameters['self_courier_name'] = $self_courier_name;
                    $parameters['self_courier_position'] = $self_courier_position;
                    // $parameters['self_courier_address'] = $self_courier_address;
                    $parameters['self_courier_telp'] = $self_courier_telp;
                    $parameters['shipped_product_img'] = $shipped_product_img;
                    $parameters['state_img'] = $state_img;
                }
            }

            if ($order instanceof Order) {
                $response = $this->updateOrderStatus($order, $origin, $parameters);
                if (isset($response['order'])) {
                    $notification = new Notification();
                    $notification->setSellerId($response['order']['seller_id']);
                    $notification->setBuyerId($response['order']['buyer_id']);
                    $notification->setIsSentToSeller(false);
                    $notification->setIsSentToBuyer(false);
                    $notification->setTitle($this->getTranslation('notifications.order_status'));
                    $notification->setContent($this->getTranslation('notifications.order_status_text', ['%invoice%' => $response['order']['invoice'], '%status%' => $response['order']['status']]));

                    $em = $this->getEntityManager();
                    $em->persist($notification);
                    $em->flush();
                }

                if($state == 'shipped') {
                    $documentApproval = new DocumentApproval();
                    $documentApproval->setOrderId($order);
                    $documentApproval->setTypeDocument('bast');
                    $documentApproval->setApprovedBy($user);
                    $documentApproval->setApprovedAt(new DateTime());
                    $documentApproval->setCreatedAt();
            
                    $documentApprovalRepository->add($documentApproval);
                }
            }
        }
        if ($state == 'shipped') {
            return $this->redirectToRoute('user_ppktreasurer_detail', ['id' => $id]);
        } else {
            return $this->view('', $response, 'json');
        }
    }

    public function complaint($id): RedirectResponse
    {
        $request = $this->getRequest();
        $user = $this->getUser();

        $this->denyAccessUnlessGranted('order.reject_received' , 'permission');
        $this->denyAccessUnlessGranted((int) $id , 'order_permission');        

        if ($request->isMethod('POST')) {
            /** @var OrderRepository $repository */
            $repository = $this->getRepository(Order::class);
            /** @var Order $order */
            $order = $repository->find($id);

            if ($order instanceof Order) {
                $description = $request->request->get('description', null);
                $translator = $this->getTranslator();

                $complaint = new OrderComplaint();
                $complaint->setOrder($order);
                $complaint->setDescription(filter_var($description, FILTER_SANITIZE_STRING));

                $em = $this->getEntityManager();
                $prevOrderValues = clone $order;
                $order->setIsApprovedPPK(false);
                $order->setStatusApprovePpk('tidak disetujui');
                $this->logOrder($this->getEntityManager(), $prevOrderValues, $order, $this->getUser());
                $em->persist($complaint);
                $em->flush();
                $em->persist($order);
                $em->flush();

                //--- Send email notification to admin
                $mailToAdmin = $this->get(BaseMail::class);
                $mailToAdmin->setMailSubject($translator->trans('message.info.new_order_complain'));
                $mailToAdmin->setMailTemplate('@__main__/email/new_order_complain.html.twig');
                $mailToAdmin->setToAdmin();
                $mailToAdmin->setMailData([
                    'order' => $order,
                    'link' => $this->generateUrl('admin_order_view', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]);
                $mailToAdmin->send();
                //--- Send email notification to admin

                $this->addFlash(
                    'success',
                    $translator->trans('message.success.complain_submitted')
                );
            }
        }
        if ($user->getSubRole() == 'PPK') {
            return $this->redirectToRoute('user_ppktreasurer_dashboard');
        } else {
            return $this->redirectToRoute('user_order_detail', ['id' => $id]);
        }
    }

    public function document($id): RedirectResponse
    {
        $request = $this->getRequest();
        $origin = $request->request->get('origin', 'invalid');
        $section = $request->request->get('section', 'invalid');
        $status = null;

        if ($request->isMethod('POST') && in_array($origin, [$this->buyerOrigin, $this->sellerOrigin], false)) {
            /** @var OrderRepository $repository */
            $repository = $this->getRepository(Order::class);
            /** @var Order $order */
            $order = $repository->find($id);
            $validator = $this->getValidator();
            $translator = $this->getTranslator();
            $input = [
                'bast' => null,
                'receipt' => null,
                'work_order_letter' => null,
                'invoice' => null,
            ];
            $rules = [];
            $defaultRule = new Constraints\Collection([
                'required' => new Constraints\NotBlank(),
                'file' => new Constraints\File([
                    'maxSize' => $this->getParameter('max_upload_file'),
                    'mimeTypes' => ['application/pdf'],
                ]),
            ]);

            if ($origin === $this->buyerOrigin) {
                if ($section === 'bast-delivery') {
                    unset($input['receipt'], $input['work_order_letter'], $input['invoice']);

                    // BAST
                    $bastFile = $request->files->get('bast', null);
                    $input['bast'] = [
                        'required' => $bastFile,
                        'file' => $bastFile,
                    ];
                    $rules['bast'] = $defaultRule;
                }

                if ($section === 'receipt-spk' && $order->getNegotiationStatus() === 'finish') {
//                    unset($input['bast'], $input['tax_invoice']);

                    // Invoice yang di upload oleh goverment di dapat dari file hardcopy dari pengiriman paket lalu di ttd dan di upload ke sistem
                    $invoiceFile = $request->files->get('invoice', null);
                    if (isset($invoiceFile)) {
                        $input['invoice'] = [
                            'required' => $invoiceFile,
                            'file' => $invoiceFile,
                        ];

                        $rules['invoice'] = $defaultRule;

                    }else {
                        unset($input['invoice']);
                    }

                    // Bast
                    $bastFile = $request->files->get('bast', null);

                    if (isset($bastFile)){
                        $input['bast'] = [
                            'required' => $bastFile,
                            'file' => $bastFile,
                        ];
                        $rules['bast'] = $defaultRule;

                    }else {
                        unset($input['bast']);
                    }

                    // Receipt
                    $receiptFile = $request->files->get('receipt', null);

                    if (isset($receiptFile)) {
                        $input['receipt'] = [
                            'required' => $receiptFile,
                            'file' => $receiptFile,
                        ];
                        $rules['receipt'] = $defaultRule;

                    }else {
                        unset($input['receipt']);
                    }

                    // Work Order Letter
                    $workOrderLetterFile = $request->files->get('work_order_letter', null);

                    if (isset($workOrderLetterFile)) {
                        $input['work_order_letter'] = [
                            'required' => $workOrderLetterFile,
                            'file' => $workOrderLetterFile,
                        ];
                        $rules['work_order_letter'] = $defaultRule;

                    }else {
                        unset($input['work_order_letter']);
                    }

                    // SPK
                    $spkFile = $request->files->get('spk', null);

                    if (isset($spkFile)) {
                        $input['spk'] = [
                            'required' => $spkFile,
                            'file' => $spkFile,
                        ];
                        $rules['spk'] = $defaultRule;

                    }else {
                        unset($input['spk']);
                    }

                    // BAPD
                    $bapdFile = $request->files->get('bapd', null);

                    if (isset($bapdFile)) {
                        $input['bapd'] = [
                            'required' => $bapdFile,
                            'file' => $bapdFile,
                        ];
                        $rules['bapd'] = $defaultRule;

                    }else {
                        unset($input['bapd']);
                    }

//                    if (($order->getTotal() + $order->getShippingPrice()) > $this->getParameter('additionalDocumentsTransaction')) {
//                        // spk_letter
//                        $spk_letter = $request->files->get('spk_letter', null);
//                        $input['spk_letter'] = [
//                            'required' => $spk_letter,
//                            'file' => $spk_letter,
//                        ];
//                        $rules['spk_letter'] = $defaultRule;
//
//                        // handover_letter
//                        $handover_letter = $request->files->get('handover_letter', null);
//                        $input['handover_letter'] = [
//                            'required' => $handover_letter,
//                            'file' => $handover_letter,
//                        ];
//                        $rules['handover_letter'] = $defaultRule;
//
//                        // handover_certificate
//                        $handover_certificate = $request->files->get('handover_certificate', null);
//                        $input['handover_certificate'] = [
//                            'required' => $handover_certificate,
//                            'file' => $handover_certificate,
//                        ];
//                        $rules['handover_certificate'] = $defaultRule;
//
//
//                        // inspection_document
//                        $inspection_document = $request->files->get('inspection_document', null);
//                        $input['inspection_document'] = [
//                            'required' => $inspection_document,
//                            'file' => $inspection_document,
//                        ];
//                        $rules['inspection_document'] = $defaultRule;
//                    }
                    if ($order->getStatus() == 'received') {
                        $status = 'document';
                    }
                }

                if ($section === 'withholding-tax-file' && $order->getStatus() === 'payment_process') {
                    unset($input['receipt'], $input['work_order_letter'], $input['invoice'], $input['bast']);

                    $withholdingTaxFile = $request->files->get('withholding-tax-slip-file', null);
                    $input['withholding_tax_slip_file'] = [
                        'required' => $withholdingTaxFile,
                        'file' => $withholdingTaxFile,
                    ];
                    $rules['withholding_tax_slip_file'] = $defaultRule;
                }
            }

            if ($origin === $this->sellerOrigin && $section === 'tax-invoice') {
                unset($input['invoice'], $input['bast'], $input['receipt'], $input['work_order_letter']);

                // Tax Invoice
                $taxInvoiceFile = $request->files->get('tax_invoice', null);
                $input['tax_invoice'] = [
                    'required' => $taxInvoiceFile,
                    'file' => $taxInvoiceFile,
                ];
                $rules['tax_invoice'] = $defaultRule;

                // $status = 'tax_invoice';
            }

            if ($section === 'receipt-spk' && count($input) < 1) {
                $input['invoice'] = [
                    'required' => null,
                    'file' => null,
                ];
                $rules['invoice'] = $defaultRule;
            }

            $constraint = new Constraints\Collection($rules);
            $violations = $validator->validate($input, $constraint);

            if (count($violations) === 0 ) {
                if ($order instanceof Order) {
                    $randomCode = StaticHelper::secureRandomCode();
                    $message = 'message.success.file_uploaded';

                    if (!empty($order->getSharedId())) {
                        $parts = explode('-', $order->getSharedId());
                        $randomCode = $parts[1] ?? $randomCode;
                    }

                    /** @var FileUploader $uploader */
                    $uploader = $this->get(FileUploader::class);
                    $uploader->setTargetDirectory(sprintf('orders/%s', $randomCode));

                    if ($origin === $this->buyerOrigin) {
                        if ($section === 'bast-delivery') {
                            /** @var UploadedFile $bast */
                            $bast = $input['bast']['file'];
                            $message = 'message.success.bast_uploaded';

                            $order->setBastFile($uploader->upload($bast, true));
                        }

                        if ($section === 'receipt-spk' && $order->getNegotiationStatus() === 'finish') {
                            if (isset($input['invoice']['file'])) {
                                /** @var UploadedFile $invoice */
                                $invoice = $input['invoice']['file'];
                                //$message = 'message.success.receipt_uploaded';

                                if (!empty($order->getInvoiceFile())) {
                                    $uploader->setOldFilePath($order->getInvoiceFile());
                                }

                                $order->setInvoiceFile($uploader->upload($invoice, true, true));
                            }

                            if (isset($input['bast']['file'])) {
                                /** @var UploadedFile $bast */
                                $bast = $input['bast']['file'];
                                //$message = 'message.success.receipt_uploaded';

                                if (!empty($order->getBastFile())) {
                                    $uploader->setOldFilePath($order->getBastFile());
                                }

                                $order->setBastFile($uploader->upload($bast, true, true));
                            }

                            if (isset($input['receipt']['file'])) {
                                /** @var UploadedFile $receipt */
                                $receipt = $input['receipt']['file'];
                                //$message = 'message.success.receipt_uploaded';

                                if (!empty($order->getReceiptFile())) {
                                    $uploader->setOldFilePath($order->getReceiptFile());
                                }

                                $order->setReceiptFile($uploader->upload($receipt, true, true));
                            }

                            if (isset($input['work_order_letter']['file'])) {
                                /** @var UploadedFile $workOrderLetter */
                                $workOrderLetter = $input['work_order_letter']['file'];
                                //$message = 'message.success.work_order_letter_uploaded';

                                if (!empty($order->getWorkOrderLetterFile())) {
                                    $uploader->setOldFilePath($order->getWorkOrderLetterFile());
                                }

                                $order->setWorkOrderLetterFile($uploader->upload($workOrderLetter, true, true));
                            }

                            if (isset($input['spk']['file'])) {
                                /** @var UploadedFile $spkFile */
                                $spkFile = $input['spk']['file'];
                                //$message = 'message.success.work_order_letter_uploaded';

                                if (!empty($order->getSpkFile())) {
                                    $uploader->setOldFilePath($order->getSpkFile());
                                }

                                $order->setSpkFile($uploader->upload($spkFile, true, true));
                            }

                            if (isset($input['bapd']['file'])) {
                                /** @var UploadedFile $bapdFile */
                                $bapdFile = $input['bapd']['file'];
                                //$message = 'message.success.work_order_letter_uploaded';

                                if (!empty($order->getBapdFile())) {
                                    $uploader->setOldFilePath($order->getBapdFile());
                                }

                                $order->setBapdFile($uploader->upload($bapdFile, true, true));
                            }

//                            if (($order->getTotal() + $order->getShippingPrice()) > $this->getParameter('additionalDocumentsTransaction')) {
//                                /** @var UploadedFile $spkLetter */
//                                $spkLetter = $input['spk_letter']['file'];
//                                if (!empty($order->getSpkLetter())) {
//                                    $uploader->setOldFilePath($order->getSpkLetter());
//                                }
//
//                                $order->setSpkLetter($uploader->upload($spkLetter, true, true));
//
//
//                                /** @var UploadedFile $handoverLetter */
//                                $handoverLetter = $input['handover_letter']['file'];
//                                if (!empty($order->getHandoverLetter())) {
//                                    $uploader->setOldFilePath($order->getHandoverLetter());
//                                }
//
//                                $order->setHandoverLetter($uploader->upload($handoverLetter, true, true));
//
//                                /** @var UploadedFile $handoverCertificate */
//                                $handoverCertificate = $input['handover_certificate']['file'];
//                                if (!empty($order->getHandoverCertificate())) {
//                                    $uploader->setOldFilePath($order->getHandoverCertificate());
//                                }
//
//                                $order->setHandoverCertificate($uploader->upload($handoverCertificate, true, true));
//
//                                /** @var UploadedFile $inspectionDocument */
//                                $inspectionDocument = $input['inspection_document']['file'];
//                                if (!empty($order->getInspectionDocument())) {
//                                    $uploader->setOldFilePath($order->getInspectionDocument());
//                                }
//
//                                $order->setInspectionDocument($uploader->upload($inspectionDocument, true, true));
//                            }

                            $message = 'message.success.negotiation_files_uploaded';
                        }

                        if ($section === 'withholding-tax-file') {

                            $withholdingTax = $input['withholding_tax_slip_file']['file'];

                            if (!empty($order->getWithholdingTaxSlipFile())) {
                                $uploader->setOldFilePath($order->getWithholdingTaxSlipFile());
                            }

                            $order->setWithholdingTaxSlipFile($uploader->upload($withholdingTax, true, true));

                            $message = 'message.success.withholding_file_uploaded';
                        }
                    }

                    if ($origin === $this->sellerOrigin && $section === 'tax-invoice') {
                        /** @var UploadedFile $taxInvoice */
                        $taxInvoice = $input['tax_invoice']['file'];
                        $message = 'message.success.tax_invoice_uploaded';
                        $prevOrderValues = clone $order;
                        $order->setTaxInvoiceFile($uploader->upload($taxInvoice, true));
                        $this->logOrder($this->getEntityManager(), $prevOrderValues, $order, $this->getUser());
                    }

                    if (!empty($status) && $order->getIsB2gTransaction()) {
                        $user_update = $this->getUser();
                        $grand_total = $order->getTotal() + $order->getShippingPrice();
                        if ($order->getPpkPaymentMethod() == 'uang_persediaan') {
                            $prevOrderValues = clone $order;
                            $order->setStatus($status);
                            $this->logOrder($this->getEntityManager(), $prevOrderValues, $order, $user_update);
                            $status = 'tax_invoice';
                            $user_update = $order->getSeller()->getUser();
                        }

                        $prevOrderValues = clone $order;
                        $order->setStatus($status);

                        $this->logOrder($this->getEntityManager(), $prevOrderValues, $order, $user_update);
                    }

                    $em = $this->getEntityManager();
                    $em->persist($order);
                    $em->flush();

                    $this->addFlash('success', $translator->trans($message));
                }
            } else {
                $errors = [];

                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }

                $flashBag = $this->get('session.flash_bag');
                $flashBag->set('errors', $errors);
            }
        }

        return $this->redirectToRoute('user_order_detail', ['id' => $id]);
    }

    public function print($id, $type): Response
    {
        if (!in_array($type, ['bast', 'label', 'invoice', 'performa_invoice', 'performa_invoice_ls', 'receipt', 'spk', 'negotiation', 'basp', 'spk_new_ls', 'spk_new', 'bapd','bapd_ls','bast_ls','receipt_ls' , 'surat-pengiriman-parsial', 'spk_non_ttd'])) {
            throw new NotFoundHttpException('Invalid print type provided!');
        }

        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        $repositoryUser = $this->getRepository(User::class);
        $order = $repository->getOrderDetail($id);
        $request = $this->getRequest();
        $auth = $this->getUser();
        // dd(json_decode($request->getContent(), true)['products']);
        // return $this->view('', 'alll', 'json');
        $deliveryBatch = (int) $request->query->get('delivery_batch', '1');
        
        /** @var Store $seller */
        $seller = $this->getUserStore();
        $qty = array_map(function ($num) {
            return $num['op_quantity'];
        }, $order['o_products']);

        $qty = array_sum($qty);

        if ($order['o_status'] === 'partial_delivery' && $type === 'bast') {
            $deliveryDetails = [];
            if (count($order['o_products']) > 0) {
                foreach ($order['o_products'] as $product) {
                    $deliveryDetails = json_decode($product['op_deliveryDetails'], true);
                    break;
                }

                $deliveryDetails = end($deliveryDetails);

                $qty = $deliveryDetails['current'];
            }
        }

        $now = time();
        $orderDate = $order['o_createdAt']->format('Y-m-d H:i:s');
        $data = [
            'date' => indonesiaDateFormatAlt($now),
            'today_date' => indonesiaDateFormatAlt($now, 'l d F Y'),
            'order_date' => indonesiaDateFormatAlt(strtotime($orderDate)),
            'order_date_day' => indonesiaDateFormatAlt(strtotime($orderDate), 'l, d F Y'),
            'order' => $order,
            'qty' => $qty,
            'delivery_batch' =>  $deliveryBatch,
        ];

        if (!empty($seller)) {
            $data['seller'] = $seller->toArray();
        } else {
            /** @var StoreRepository $storeRepository */
            $storeRepository = $this->getRepository(Store::class);
            $store = $storeRepository->findOneBy(['user' => $order['s_ow_id']]);
            $data['seller'] = $store ? $store->toArray() : [];
        }

        $data['data_ppk'] = [];
        if ($order['o_ppkId']){
            $data_ppk = $repositoryUser->find($order['o_ppkId']);
            $data['data_ppk'] = $data_ppk;
        }

        $data['data_treasurer'] = [];
        if ($order['o_treasurerId']){
            $data_treasurer = $repositoryUser->find($order['o_treasurerId']);
            $data['data_treasurer'] = $data_treasurer;
        }

        $data['data_buyer'] = [];
        if ($order['u_id']){
            $data_buyer = $repositoryUser->find($order['u_id']);
            $data['data_buyer'] = $data_buyer;
        }

        if(!is_null($request->query->get('data_preview')) && $type == 'surat-pengiriman-parsial') {
            $dataPreview = json_decode(base64_decode($request->query->get('data_preview')));


            $order_partials = [];

            if(!is_null($order['o_master_id'])) {
                $order_partials = $repository->getPartialOrders($order['o_master_id']);
            }else if (is_null($order['o_master_id']) && $order['o_type_order'] == 'master') {
                $order_partials = $repository->getPartialOrders($order['o_id']);
            }

            $data['order']['o_invoice'] = $data['order']['o_invoice'] . '-' . (count($order_partials) + 1);
            $data['order']['o_createdAt'] = date('Y-m-d H:i:s');
            $data['order']['o_unit_note'] =  $dataPreview->unit_note;
            $data['order']['o_address_note'] = $dataPreview->address_notes;
            $data['order']['o_note'] = $dataPreview->note;
            $data['order']['o_sendAt'] = $dataPreview->sendAt;

            foreach ($data['order']['o_products'] as $key => $product) {
                if($product['op_id'] == $dataPreview->order_products[$key]->id) {
                    $data['order']['o_products'][$key]['op_quantity'] = $dataPreview->order_products[$key]->quantity_to_send;
                    $data['order']['o_negotiatedProducts'][$key]['on_negotiatedShippingPrice'] = $dataPreview->shipping_price;
                    $data['order']['o_negotiatedProducts'][$key]['on_taxNominalShipping'] = $dataPreview->tax_nominal_shipping;
                }
            }


        }

        $data['auth'] = $auth;
        
        $documentApprovalRepository = $this->getRepository(DocumentApproval::class);
        $data['order']['o_documentApprovals'] = $documentApprovalRepository->getByTypeDocument($order['o_id'], $type);

        return $this->handlePrint($type, $data);
    }

    public function printWord($id, $type): Response
    {
        if (!in_array($type, ['bast', 'label', 'invoice', 'performa_invoice','performa_invoice_ls','receipt', 'spk', 'negotiation', 'spk_new_ls','spk_new','bapd','bapd_ls','bast_ls','receipt_ls', 'spk_non_ttd'])) {
            throw new NotFoundHttpException('Invalid print type provided!');
        }

        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        $order = $repository->getOrderDetail($id);
        // dd($order);
        //if ($order['u_role'] !== 'ROLE_USER_GOVERNMENT') {
        //    return $this->redirectToRoute('user_order_detail', ['id' => $id]);
        //}

        /** @var Store $seller */
        $seller = $this->getUserStore();
        $qty = array_map(function ($num) {
            return $num['op_quantity'];
        }, $order['o_products']);

        $now = time();
        $orderDate = $order['o_createdAt']->format('Y-m-d H:i:s');
        $data = [
            'date' => indonesiaDateFormatAlt($now),
            'today_date' => indonesiaDateFormatAlt($now, 'l d F Y'),
            'order_date' => indonesiaDateFormatAlt(strtotime($orderDate)),
            'order_date_day' => indonesiaDateFormatAlt(strtotime($orderDate), 'l, d F Y'),
            'order' => $order,
            'seller' => [],
            'qty' => array_sum($qty),
        ];

        if (!empty($seller)) {
            $data['seller'] = $seller->toArray();
        } else {
            //$userRepository = $this->getRepository(User::class);
            //$data['seller'] = ['user' => $userRepository->find($order['s_ow_id'])];

            /** @var StoreRepository $storeRepository */
            $storeRepository = $this->getRepository(Store::class);
            $store = $storeRepository->findOneBy(['user' => $order['s_ow_id']]);
            $data['seller'] = $store ? $store->toArray() : [];
        }

        return $this->handlePrintWord($type, $data);
    }

    public function negotiate($id)
    {
        
        $this->isAjaxRequest('POST');
        $this->denyAccessUnlessGranted((int) $id , 'order_permission');    
        $this->denyAccessUnlessGranted((int) $id , 'order_negotiation');    

        $request = $this->getRequest();
        $translator = $this->getTranslator();
        $prices = (array)$request->request->get('prices', '');
        $time = $request->request->get('time', '');
        $note = $request->request->get('note', '');
        $shipping = $request->request->get('shipping', '');
        $submittedAs = $request->request->get('submitted_as', '');
        $submittedBy = 'unknown';
        $values = [];
        $response = [
            'status' => false,
            'message' => null,
        ];
        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        $orderProducts = $repository->getOrderProducts($id);
        $tmpTotal = 0;
        $tmpTotal += $shipping;
        $b2gLimitForNonPkp = $this->getParameter('b2gLimitAmountForNonPkpStore');
        $b2gLimitForPkp = $this->getParameter('b2gLimitAmountForPkpStore');

//        dd($prices);

        /** @var Order $order */
        $order = $repository->find($id);
        /** @var Store $store */
        $store = $order->getSeller();
        $withTax = true;
        $productRepository = $this->getRepository(Product::class);
        $freeTaxForCategoryList = $this->getParameter('free_tax_for_category');

        foreach ($prices as $price) {
            if (!empty($price['value'])) {
                $index = str_replace('price_', '', $price['name']);
                $tmpTotal += (float) $price['value'];
                $isPkp = $store->getIsPKP();

                $product = $productRepository->find($index);

                if ($product instanceof Product) {
                    if (in_array($product->getCategory(), $freeTaxForCategoryList, false)) {
                       $isPkp = false;
                       $withTax = false;
                       $values[$index]['withTax'] = false;
                    }else {
                        $values[$index]['withTax'] = $withTax;
                    }
                }
                
                if ($withTax) {
                    $values[$index]['price'] = $price['value'] / ($this->getPpnPercentage($store->getUmkmCategory()) + 1); // Karena harga yg di kirim adalah total harga + PPN 10 %
                } else {
                    $values[$index]['price'] = $price['value'];
                }

                foreach ($orderProducts as $orderProduct) {
                    if ((int)$orderProduct['p_id'] === (int)$index) {
                        $values[$index]['price'] /= $orderProduct['op_quantity']; // Mencari harga untuk 1 item
                    }
                }
            }
        }

        if (!empty($time) && in_array($submittedAs, ['buyer', 'seller'], false) && count($values) > 0) {
            $count = $repository->countNegotiationSubmissions($id);

            // if ($withTax) {
                $shipping /= $this->getPpnPercentage($store->getUmkmCategory()) + 1; // Balikin ke shipping tanpa PPN 10%
            // }

            $em = $this->getEntityManager();

            if ($submittedAs === 'buyer') {
                /** @var User $buyer */
                $buyer = $order->getBuyer();
                $submittedBy = $buyer->getId();
                $userSubmit = $buyer;
            } elseif ($submittedAs === 'seller') {
                /** @var User $seller */
                $seller = $store->getUser();
                $submittedBy = $seller->getId();
                $userSubmit = $seller;
            }

            //    if ($order->getIsB2gTransaction() && !$order->getSeller()->getIsPKP() && $tmpTotal > $b2gLimitForNonPkp) {
            //        $response['message'] = $translator->trans('message.error.b2g_over_limit');

            //        return $this->view('', $response, 'json');
            //    }

            if ($order->getIsB2gTransaction() && $order->getSeller()->getIsPKP() && $tmpTotal > $b2gLimitForPkp) {
                $response['message'] = $translator->trans('message.error.b2g_pkp_over_limit');

                return $this->view('', $response, 'json');
            }
            $previousOrderValues = clone $order;

            foreach ($values as $key => $value) {

                if ($value['withTax']) {
                    $taxNominalPrice = round($value['price'] * $this->getPpnPercentage($store->getUmkmCategory()), 1);
                    $taxValue = $this->getPpnPercentage($store->getUmkmCategory()) * 100;
                } else {
                    $taxNominalPrice = $taxNominalShipping = $taxValue = 0;
                }

                // if ($value['withTax']) {
                    $taxNominalShipping = round($shipping * $this->getPpnPercentage($store->getUmkmCategory()), 1);
                // }

                $orderNegotiation = new OrderNegotiation();
                $orderNegotiation->setOrder($order);
                $orderNegotiation->setProductId($key);
                $orderNegotiation->setSubmittedBy($submittedBy);
                $orderNegotiation->setSubmittedAs($submittedAs);
                $orderNegotiation->setNegotiatedPrice((float)$value['price']); #harga per item tanpa pajak
                $orderNegotiation->setTaxNominalPrice((float)$taxNominalPrice); #nominal ppn
                $orderNegotiation->setExecutionTime($time);
                $orderNegotiation->setIsApproved(false);
                $orderNegotiation->setBatch((int)$count['total'] + 1);
                $orderNegotiation->setNote($note);
                $orderNegotiation->setNegotiatedShippingPrice((float)$shipping); #di isi tanpa pajak
                $orderNegotiation->setTaxNominalShipping((float)$taxNominalShipping); #nominal ppn ongkir
                $orderNegotiation->setTaxValue((float)$taxValue); #value ppn 10% = 10
                
                $order->setBatchNego((int)$count['total'] + 1);

                $em->persist($orderNegotiation);
                $em->flush();
                $em->persist($order);
                $em->flush();
            }
            
            $this->logOrder($em, $previousOrderValues, $order, $userSubmit);

            $response['status'] = true;

        } else {
            $response['message'] = $translator->trans('message.error.check_negotiation_fields');
        }

        return $this->view('', $response, 'json');
    }

    public function approveNegotiation($id)
    {
        $this->isAjaxRequest('POST');
        $request = $this->getRequest();
        $origin = $request->request->get('origin', 'invalid');

        $this->denyAccessUnlessGranted('order.approve_negotiation' , 'permission');
        $this->denyAccessUnlessGranted((int) $id , 'order_permission');
        $this->denyAccessUnlessGranted($origin, 'order_negotiation_approve');
        

        $users = $this->getUser();

        $translator = $this->getTranslator();
        
        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        $count = $repository->countNegotiationSubmissions($id);
        $response = [
            'status' => false,
            'message' => $translator->trans('message.error.global'),
        ];

        if ((int)$count['total'] > 0) {
            /** @var Order $order */
            $order = $repository->find($id);
            /** @var OrderNegotiation[] $negotiations */
            $negotiations = $repository->getLatestNegotiationItem($id);

            if ($order->getStatus() == 'new_order') {
                $em = $this->getEntityManager();
                $order->setStatus('confirmed');
                $em->persist($order);
                $em->flush();
            }

            if (count($negotiations) > 0) {
                $em = $this->getEntityManager();
                $grandTotal = 0;
                $shippingTotal = 0;
                $isApproved = false;

                foreach ($negotiations as $negotiation) {
                    if ($origin === $this->sellerOrigin) {
                        $negotiation->setMerchantApproval(true);
                    } elseif ($origin === $this->buyerOrigin) {
                        if (!$negotiation->getMerchantApproval()) {
                            $negotiation->setMerchantApproval(true);
                        }

                        $negotiation->setCustomerApproval(true);
                    }

                    if ($negotiation->getMerchantApproval() && $negotiation->getCustomerApproval()) {
                        $isApproved = true;

                        $negotiation->setIsApproved($isApproved);
                    }

                    $em->persist($negotiation);
                    $em->flush();

                    foreach ($order->getOrderProducts() as $orderProduct) {
                        /** @var OrderProduct $product */
                        $product = $orderProduct->getProduct();

                        if ((int)$product->getId() === $negotiation->getProductId()) {
                            $price = $negotiation->getNegotiatedPrice();
                            $shippingPrice = $negotiation->getNegotiatedShippingPrice();
                            $shippingTotal = $shippingPrice + $negotiation->getTaxNominalShipping();
                            $quantity = $orderProduct->getQuantity();
                            $backupPrice = $product->getPrice();
                            $totalPrice = $price * $quantity;
                            $grandTotal += $totalPrice;

                            if ((int)$orderProduct->getWithTax() === 1 && (int)$orderProduct->getTaxValue() > 0) {
                                //$taxNominal = (round($negotiation->getNegotiatedPrice() - ($negotiation->getNegotiatedPrice() / 1.1)) * $quantity) + round($shippingPrice - ($shippingPrice / 1.1));
                                $taxNominal = $totalPrice * ($orderProduct->getTaxValue() / 100);
                                $grandTotal += $taxNominal;

                                $orderProduct->setTaxNominal($taxNominal);
                            }

                            $orderProduct->setPrice($price);
                            $orderProduct->setPriceBeforeNegotiation($backupPrice);
                            $orderProduct->setTotalPrice($totalPrice);
                            $orderProduct->setPriceShippingNegotiation($shippingPrice);

                            $em->persist($orderProduct);
                            $em->flush();
                        }
                    }
                }

                if ($isApproved) {
                    $previousOrderValues = clone $order;

                    // if ($order->getBuyer()->getSubRole() == 'PPK') {
                        // $order->setStatus('processed');
                        $order->setStatus('confirm_order_ppk');
                        $order->setUpdatedAt();
                        // $order->setIsApprovedOrderPPK(true);
                    // } else {
                    //     $order->setStatus('approve_order_ppk');
                    // }

                    $order->setNegotiationStatus('finish');
                    $order->setExecutionTime($negotiations[0]->getExecutionTime());
                    $order->setStatusChangeTime();

                    $this->logOrder($em, $previousOrderValues, $order, $order->getBuyer());

                    if ($grandTotal > 0) {
                        $order->setTotal($grandTotal);
                    }

                    if ($shippingTotal >= 0) {
                        $order->setShippingPrice($shippingTotal);
                    }

                    $order->setShippingPrice($shippingTotal);

                    $em->persist($order);
                    $em->flush();
                    if ((empty($order->getErzapOrderReport()) || $order->getErzapOrderReport() === 'failed') && $order->getSeller()->getIsUsedErzap() == true) {
                        $this->erzapOrderWebhook($order->getSharedId(), $em);
                    }

                    /**
                     * Send email to seller
                     */
                    try {
                        /** @var Store $seller */
                        $seller = $order->getSeller();
                        /** @var User $owner */
                        $owner = $seller->getUser();
                        /** @var BaseMail $mailToSeller */
                        $mailToSeller = $this->get(BaseMail::class);
                        $mailToSeller->setMailSubject($this->getTranslator()->trans('message.info.order_negotiation_approved'));
                        $mailToSeller->setMailTemplate('@__main__/email/order_negotiation_approved.html.twig');
                        $mailToSeller->setMailRecipient($owner->getEmailCanonical());
                        $mailToSeller->setMailData([
                            'store_name' => $seller->getName(),
                            'invoice' => $order->getInvoice(),
                            'recipient_type' => 'seller',
                            'link' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
                        ]);
                        $mailToSeller->send();

                    } catch (\Throwable $exception) {

                    }

                }

                $response['status'] = true;
                $response['message'] = null;
            }
        }

        return $this->view('', $response, 'json');
    }

    public function erzapOrderWebhook(string $orderSharedId, $em)
    {
        $repository = $this->getRepository(Order::class);
        $orders = $repository->findBy(['sharedId' => $orderSharedId]);

        if (count($orders) > 0) {
            foreach ($orders as $order) {
                $request = [];
                $request['order_id'] = $order->getInvoice();
                $request['order_status'] = $order->getStatus();
                $request['shipping_courrier'] = $order->getShippingCourier().'('.$order->getShippingService().')';
                $request['order_notes'] = $order->getNote();
                $request['order_time'] = $order->getCreatedAt()->getTimestamp();
                $request['shop_id'] = $order->getSeller()->getShopId();

                // Product
                $orderProducts = $repository->getOrderProducts($order->getId());
                $produk_mp = [];

                if (count($orderProducts) > 0) {
                    foreach ($orderProducts as $p) {
                        $produk['idproduk_mp'] = $p['p_id'];
                        $produk['nama_produk'] = $p['p_name'];
                        $produk['quantity'] = (int)$p['op_quantity'];
                        $produk['notes'] = $p['op_note'];
                        $produk['harga_jual'] = (int)$p['p_price'];
                        $produk['total_harga'] = (int)$p['p_price'] * (int)$p['op_quantity'];
                        $produk['sku'] = $p['p_sku'];
                        $produk_mp[] = $produk;
                    }
                }
                $request['produk_mp'] = $produk_mp;

                $reciept['name'] = $order->getBuyer()->getUsername();
                $reciept['phone'] = $order->getBuyer()->getPhoneNumber();

                // address
                $address['address_full'] = $order->getAddress();
                $address['district'] = $order->getDistrict();
                $address['city'] = $order->getCity();
                $address['province'] = $order->getProvince();
                $address['country'] = $order->getCountry();
                $address['postal_code'] = $order->getPostCode();

                $reciept['address'] = $address;

                $request['recipient'] = $reciept;

                // Price
                $price['subtotal'] = (int)$order->getTotal();
                $price['biaya_kirim'] = (int)$order->getShippingPrice();
                $price['discount'] = 0;
                $price['total_payment'] = (int)($order->getTotal() + $order->getShippingPrice());
                $request['price'] = $price;

                $erzapReportStatus = 'failed';

                try {
                    $headers = [
                        'Content-Type' => 'application/json',
                    ];
                    $options = ['headers' => $headers, 'json' => $request];
                    $endpoint = getenv('ERZAP_WEBHOOK_ENDPOINT');
                    $result = HttpClientService::run($endpoint, $options, 'POST');

                    if ($result['error'] === false) {
                        $erzapReportStatus = 'sent';
                        $this->logger->error('ERZAP Order webhook success', $result);

                    } else {
                        $this->logger->error('ERZAP Order webhook failed', $result);
                    }

                } catch (\Throwable $th) {
                    $this->logger->error('ERZAP Order webhook error', [$th->getMessage()]);
                }

                $order->setErzapOrderReport($erzapReportStatus);
                $em->persist($order);
                $em->flush();
            }
        }
    }

    private function routeGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            $query = ['page' => $page];

            if (isset($parameters['keywords'])) {
                $query['keywords'] = $parameters['keywords'];
            }

            if (isset($parameters['status'])) {
                $query['status'] = $parameters['status'];
            }

            return $this->get('router')->generate('user_order_index', $query);
        };
    }

    private function updateOrderStatus(Order $order, string $origin, array $parameters): array
    {
        $status = $parameters['status'] ?? 'invalid';
        $orderStatus = $order->getStatus();
        $response = [
            'status' => false,
            'content_status' => '',
            'content_buttons' => '',
            'shared_id' => '',
        ];

        if ($status == 'cancel') {
            $em = $this->getEntityManager();
            $order->setUserCancelOrder($parameters['user_type']);
            $previousOrderValues = clone $order;
            $order->setCancelReason($parameters['reason_cancel']);
            if ($orderStatus == 'confirmed' || $orderStatus == 'new_order') {
                $order->setCancelStatus('');
                /** @var OrderProduct[] $orderProducts */
                $orderProducts = $order->getOrderProducts();
                $em = $this->getEntityManager();

                // Increment product quantity?
                foreach ($orderProducts as $orderProduct) {
                    /** @var Product $product */
                    $product = $orderProduct->getProduct();
                    $stock = $product->getQuantity();
                    $newStock = $stock + $orderProduct->getQuantity();

                    $product->setQuantity($newStock < 1 ? 0 : $newStock);

                    $em->persist($product);
                }

                $em->flush();
                $response = $this->setOrderStatus($order, $status);
                $this->logOrder($em, $previousOrderValues, $order, $this->getUser());
                
                
            } else {
                $response['status'] = true;
                $order->setStatus('cancel_request');
                $this->logOrder($em, $previousOrderValues, $order, $this->getUser());
                $order->setCancelStatus($order->getStatus());
                $order->setStatusChangeTime();
            }
            $em->persist($order);
            $em->flush();
        }

        if ($origin === $this->buyerOrigin) {

            if ($order->getIsB2gTransaction()) {
                if ($status === 'pending_payment') {
                    $response = $this->setOrderStatus($order, $status);
                }

                if ($status === 'tax_invoice') {
                    $response = $this->setOrderStatus($order, 'pending_payment');
                }

                if ($status === 'paid') {
                    $response = $this->setOrderStatus($order, 'finished');
                }

                if ($orderStatus === 'shipped' && $status === 'received') {
                    $user_update = $this->getUser();
                    $grand_total = $order->getTotal() + $order->getShippingPrice();
                    
                    $status = 'pending_approve';
                    if ($order->getBuyer()->getSubRole() == 'PPK') {
                        $status = 'pending_payment';
                        $em = $this->getEntityManager();
                        $order->setIsApprovedPPK(true);
                        $order->setStatusApprovePpk('disetujui');
                        $em->persist($order);
                        $em->flush();
                    }
                    
                    $em = $this->getEntityManager();
                    // $this->setDisbursementProductFee($em, $order);
                    $response = $this->setOrderStatus($order, $status);

                    

                }
            }
        } elseif ($origin === $this->sellerOrigin) {
            if ($orderStatus === 'pending' && $status === 'cancel') {
                /** @var OrderProduct[] $orderProducts */
                $orderProducts = $order->getOrderProducts();
                $em = $this->getEntityManager();

                // Increment product quantity?
                foreach ($orderProducts as $orderProduct) {
                    /** @var Product $product */
                    $product = $orderProduct->getProduct();
                    $stock = $product->getQuantity();
                    $newStock = $stock + $orderProduct->getQuantity();

                    $product->setQuantity($newStock < 1 ? 0 : $newStock);

                    $em->persist($product);
                }

                $em->flush();

                $response = $this->setOrderStatus($order, $status);
            }

            if ($orderStatus === 'payment_process' && $status === 'paid') {
                $response = $this->setOrderStatus($order, $status);
            }

            if ($orderStatus === 'paid' && $status === 'processed') {
                $response = $this->setOrderStatus($order, $status);
            }

            if (($orderStatus === 'processed' || $orderStatus === 'partial_delivery') && $status === 'shipped') {
                /** @var OrderProduct[] $orderProducts */
                $orderProducts = $order->getOrderProducts();
                $qty = $parameters['delivery_qty'] ?? [];
                $pid = $parameters['delivery_pid'] ?? [];
                $isComplete = [];
                $productCount = count($orderProducts);
                $em = $this->getEntityManager();

                foreach ($orderProducts as $orderProduct) {
                    $details = $orderProduct->getDeliveryDetails();
                    /** @var Product $product */
                    $product = $orderProduct->getProduct();
                    $productId = $product->getId();

                    $productIsBeingPartial = in_array($productId, $pid, false);
                    if ($productIsBeingPartial == false) {
                        $isComplete[] = $productId;
                    }

                    if (in_array($productId, $pid, false)) {
                        $key = array_search($productId, $pid, false);
                        $quantity = $qty[$key] ?? 0;
                        $complete = false;

                        if ($quantity < 1) {
                            $isComplete[] = $productId;
                            continue;
                        }

                        if (count($details) > 0) {
                            $batch = count($details) + 1;
                            $latest = end($details);
                            $remaining = $latest['remaining'] - $quantity;

                            if ($remaining < 1) {
                                $complete = true;
                            }

                            $details[] = [
                                'batch' => $batch,
                                'total' => (int)$orderProduct->getQuantity(),
                                'current' => (int)$quantity,
                                'remaining' => $remaining,
                                'submitted_at' => date('Y-m-d H:i:s'),
                                'tracking_code' => $parameters['waybill'],
                            ];
                        } else {
                            $remaining = (int)($orderProduct->getQuantity() - $quantity);

                            if ($remaining < 1) {
                                $complete = true;
                            }

                            $details = [
                                [
                                    'batch' => 1,
                                    'total' => (int)$orderProduct->getQuantity(),
                                    'current' => (int)$quantity,
                                    'remaining' => $remaining,
                                    'submitted_at' => date('Y-m-d H:i:s'),
                                    'tracking_code' => $parameters['waybill'],
                                ],
                            ];
                        }

                        $orderProduct->setDeliveryDetails($details);
                        $em->persist($orderProduct);
                        $em->flush();

                        if ($complete) {
                            $isComplete[] = $productId;
                        }
                    }
                }

                if (count($isComplete) !== $productCount) {
                    $status = 'partial_delivery';
                }

                $arrData = [
                    'trackingCode' => $parameters['waybill'],
                    'shipped_method' => $parameters['shipped_method'],
                    'self_courier_name' => $parameters['self_courier_name'],
                    'self_courier_position' => $parameters['self_courier_position'],
                    // 'self_courier_address' => $parameters['self_courier_address'],
                    'self_courier_telp' => $parameters['self_courier_telp'],
                    'shipped_product_img' => $parameters['shipped_product_img'],
                    'state_img' => $parameters['state_img'],

                ];
                $response = $this->setOrderStatus($order, $status, $arrData);
            }

        }

        return $response;
    }

    private function setOrderStatus(Order $order, string $status, array $data = null): array
    {
        $previousOrderValues = clone $order;

        if ($order->getStatus() !== $status) {
            $order->setStatusChangeTime();
        }

        $order->setStatus($status);
        $order->setUpdatedAt();

        $em = $this->getEntityManager();
        if ($status == 'pending_payment' || $status == 'received') {
            $order->setReceivedAt();
        }

        if ($status === 'shipped') {

            $randomCode = StaticHelper::secureRandomCode();

            /** @var FileUploader $uploader */
            $uploader = $this->get(FileUploader::class);
            $uploader->setTargetDirectory(sprintf('orders/%s', $randomCode));

            if (isset($data['state_img']) && !empty($data['state_img'])) {
                $order->setStateImg($uploader->upload($data['state_img'], true));
            }

            

            $order->setTrackingCode($data['trackingCode']);
            $order->setShippedMethod($data['shipped_method']);
            $order->setSelfCourierName($data['self_courier_name']);
            $order->setSelfCourierPosition($data['self_courier_position']);
            // $order->setSelfCourierAddress($data['self_courier_address']);
            $order->setSelfCourierTelp($data['self_courier_telp']);
            $order->setShippedAt();
            $order->setUpdatedAt();
        }
        $em->persist($order);
        $em->flush();

        if ($status === 'shipped') {
            if (isset($data['shipped_product_img']) && !empty($data['shipped_product_img'])) {
                /** @var OrderShippedFileRepository $repository */
                $repoShippedFile = $this->getRepository(OrderShippedFile::class);
                $em = $this->getEntityManager();
                foreach ($data['shipped_product_img'] as $key => $value) {
                    $orderShippedFile = new OrderShippedFile();
                    $orderShippedFile->setOrder($order);
                    $orderShippedFile->setFilePath($uploader->upload($value, true));
                    $em->persist($orderShippedFile);
                    $em->flush();
                }
            }
        }

        

        

        $templates = $this->statusTemplates();
        /** @var DateTime $updatedAt */
        $updatedAt = $order->getUpdatedAt();
        /** @var User $buyer */
        $buyer = $order->getBuyer();
        /** @var Store $store */
        $store = $order->getSeller();
        /** @var User $seller */
        $seller = $store->getUser();
        $contentStatus = $contentButtons = '';

//        if ($status !== 'pending_payment') {
//            $contentStatus = sprintf($templates[$status]['status'], $updatedAt->format('d/m/Y - H:i'));
//            $contentButtons = $templates[$status]['buttons'];
//        }

        if ($status != 'cancel') {
            if ($status === 'tax_invoice' || $status === 'shipped') {
                $this->logOrder($em, $previousOrderValues, $order, $seller);
            }else if ($status === 'partial_delivery') {
                $this->logOrder($em, $previousOrderValues, $order, $seller);
            } else if ($status == 'pending_payment') {
                $this->logOrder($em, $previousOrderValues, $order, $this->getUser());
            
            } else {
                $this->logOrder($em, $previousOrderValues, $order, $buyer);
            }
        }

        if (!$order->getIsB2gTransaction() && $status === 'received') {
            $this->setDisbursementProductFee($em, $order);
        }

        if ($status === 'shipped') {
            $buyer = $order->getBuyer();
            $mailToBuyer = $this->get(BaseMail::class);
            $mailToBuyer->setMailSubject($this->getTranslator()->trans('message.info.shipped'));
            $mailToBuyer->setMailTemplate('@__main__/email/order_shipped.html.twig');
            $mailToBuyer->setMailRecipient($buyer->getEmailCanonical());
            $mailToBuyer->setMailData([
                'name' => $buyer->getFirstName(),
                'invoice' => $order->getInvoice(),
                'link' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            $mailToBuyer->send();

            $repoPic = $this->getRepository(UserPicDocument::class);
            $repoPPK = $this->getRepository(UserPpkTreasurer::class);

            if (!empty($order->getUnitName()) && !empty($order->getUnitEmail())) {

                $data_pic = $repoPic->findOneBy(['email' => $order->getUnitEmail()]);
                /**
                 * Send email to pic
                 */
                try {
                    /** @var BaseMail $mailToSeller */
                    $mailToSeller = $this->get(BaseMail::class);
                    $mailToSeller->setMailSubject('Bmall '.$order->getInvoice().'_Approval PPK');
                    $mailToSeller->setMailTemplate('@__main__/email/sent_to_pic.html.twig');
                    $mailToSeller->setMailRecipient($order->getUnitEmail());
                    $mailToSeller->setMailData([
                        'name' => $order->getUnitName(),
                        'invoice' => $order->getInvoice(),
                        'pp' => $order->getName(),
                        'ppk_name' => $order->getPpkName(),
                        'satker' => $data_pic->getSatker(),
                        'klpd' => $data_pic->getKlpd(),
                        'merchant' => $order->getSeller()->getName(),
                        'payment_method' => $order->getPpkPaymentMethod(),
                        'status' => 'received',
                        'type' => 'pic',
                        'link_login' => getenv('APP_URL').'/login',
                        'link_confirm' => $this->generateUrl('ppk_approve', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_i' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_st' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'performa_invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_b' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'bast'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_sp' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'spk'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_k' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'receipt'], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]);
                    $mailToSeller->send();

                } catch (\Throwable $exception) {
                    // $this->logger->error('Send Email PIC Throwable', [$exception->getMessage()]);
                }
            }

            if (!empty($order->getPpkName()) && !empty($order->getPpkEmail())) {

                $data_ppk = $repoPPK->findOneBy(['email' => $order->getPpkEmail()]);
                /**
                 * Send email to ppk
                 */
                try {
                    /** @var BaseMail $mailToSeller */
                    $mailToSeller = $this->get(BaseMail::class);
                    $mailToSeller->setMailSubject('Bmall '.$order->getInvoice().'_Approval PPK');
                    $mailToSeller->setMailTemplate('@__main__/email/sent_to_pic.html.twig');
                    $mailToSeller->setMailRecipient($order->getPpkEmail());
                    $mailToSeller->setMailData([
                        'name' => $order->getPpkName(),
                        'invoice' => $order->getInvoice(),
                        'pp' => $order->getName(),
                        'ppk_name' => $order->getPpkName(),
                        'satker' => $data_ppk->getSatker(),
                        'klpd' => $data_ppk->getKldi(),
                        'merchant' => $order->getSeller()->getName(),
                        'payment_method' => $order->getPpkPaymentMethod(),
                        'status' => 'received',
                        'type' => 'ppk',
                        'link_login' => getenv('APP_URL').'/login?email='.$order->getPpkEmail(),
                        'link_confirm' => $this->generateUrl('ppk_approve', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_i' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_st' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'performa_invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_b' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'bast'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_sp' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'spk'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_k' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'receipt'], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]);
                    $mailToSeller->send();

                } catch (\Throwable $exception) {
                    $this->logger->error('Send Email PPK Throwable', [$exception->getMessage()]);
                }
            }
        } elseif ($status === 'received') {
            /** @var Store $seller */
            $seller = $order->getSeller();
            /** @var User $owner */
            $owner = $seller->getUser();
            /** @var BaseMail $mailToSeller */
            $mailToSeller = $this->get(BaseMail::class);
            $mailToSeller->setMailSubject($this->getTranslator()->trans('message.info.received'));
            $mailToSeller->setMailTemplate('@__main__/email/order_received.html.twig');
            $mailToSeller->setMailRecipient($owner->getEmailCanonical());
            $mailToSeller->setMailData([
                'name' => $owner->getFirstName(),
                'invoice' => $order->getInvoice(),
                'recipient_type' => 'seller',
                'link' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            $mailToSeller->send();

        }

        return [
            'status' => true,
            'content_status' => $contentStatus,
            'content_buttons' => $contentButtons,
            'shared_id' => $order->getSharedId(),
            'order' => [
                'title' => 'Order Status',
                'invoice' => $order->getInvoice(),
                'status' => $order->getStatus(),
                'seller_id' => $seller->getId(),
                'buyer_id' => $buyer->getId(),
            ],
        ];
    }

    private function statusTemplates(): array
    {
        $translator = $this->getTranslator();
        $received = $translator->trans('label.received');
        $cancelled = $translator->trans('label.cancelled');
        $cancelledMessage = $translator->trans('message.info.cancelled');
        $confirmed = $translator->trans('label.confirmed');
        $confirmedMessage = $translator->trans('message.info.confirmed');
        $processed = $translator->trans('label.processed');
        $processedMessage = $translator->trans('message.info.processed');
        $shipped = $translator->trans('label.shipped');
        $shippedMessage = $translator->trans('message.info.shipped');

        return [
            'received' => [
                'status' => '<p class="green"><span>' . $received . '</span> (%s)</p>',
                'buttons' => '',
            ],
            'cancel' => [
                'status' => '<p><span>' . $cancelled . '</span> (%s)</p>',
                'buttons' => '<span class="gBtn red" style="cursor: default;">' . $cancelledMessage . '</span>',
            ],
            'confirmed' => [
                'status' => '<p class="yellow"><span>' . $confirmed . '</span> (%s)</p>',
                'buttons' => '<a href="javascript:void(0);" class="sBtn red seller-act-order" data-state="processed">' . $confirmedMessage . '</a>',
            ],
            'processed' => [
                'status' => '<p class="blue"><span>' . $processed . '</span> (%s)</p>',
                'buttons' => '<a href="javascript:void(0);" class="sBtn red seller-act-order" data-state="shipped">' . $processedMessage . '</a>',
            ],
            'shipped' => [
                'status' => '<p class="green"><span>' . $shipped . '</span> (%s)</p>',
                'buttons' => '<span class="sBtn red" style="cursor: default;">' . $shippedMessage . '</span>',
            ],
        ];
    }

    private function handlePrint(string $type, array $data, array $parameters = []): Response
    {
        $fileName = 'document.pdf';
        $font = isset($parameters['font']) && !empty($parameters['font']) ? $parameters['font'] : 'Arial';
        $paperSize = isset($parameters['paper_size']) && !empty($parameters['paper_size']) ? $parameters['paper_size'] : 'A4';
        $paperOrientation = isset($parameters['paper_orientation']) && !empty($parameters['paper_orientation']) ? $parameters['paper_orientation'] : 'portrait';

        $options = new Options();
        $options->set('defaultFont', $font);

        // if (isset($parameters['enable_remote']) && $parameters['enable_remote'] === true) {
            $options->set('isRemoteEnabled', true);
        // }


        if ($type === 'negotiation') {
            $fs = new Filesystem();
            $pdf = new Dompdf($options);

            $orderId = $data['order']['o_id'];
            $dirPath = __DIR__ . '/../../../var/pdf/negotiation';
            $uploadPath = $this->getParameter('upload_dir_path');
            $labelFilePath = $dirPath . '/' . $orderId . '/label.pdf';
            $bastFilePath = $dirPath . '/' . $orderId . '/tanda_terima.pdf';
            //$receiptFilePath = $dirPath.'/'.$orderId.'/receipt.pdf';
            //$spkFilePath = $dirPath.'/'.$orderId.'/spk.pdf';
            $receiptFilePath = $uploadPath . '/' . $data['order']['o_receiptFile'];
            $spkFilePath = $uploadPath . '/' . $data['order']['o_workOrderLetterFile'];

            if (!file_exists($labelFilePath)) {
                $label = $pdf;
                $label->loadHtml($this->renderView('@__main__/public/user/order/print/label.html.twig', $data));
                $label->setPaper($paperSize, $paperOrientation);
                $label->render();
                $fs->appendToFile($labelFilePath, $label->output());
            }

            if (!file_exists($bastFilePath)) {
                $bast = $pdf;
                $bast->loadHtml($this->renderView('@__main__/public/user/order/print/bast.html.twig', $data));
                $bast->setPaper($paperSize, $paperOrientation);
                $bast->render();
                $fs->appendToFile($bastFilePath, $bast->output());
            }

            /*if (!file_exists($receiptFilePath)) {
                $receipt = $pdf;
                $receipt->loadHtml($this->renderView('@__main__/public/user/order/print/receipt.html.twig', $data));
                $receipt->setPaper($paperSize, $paperOrientation);
                $receipt->render();
                $fs->appendToFile($receiptFilePath, $receipt->output());
            }*/

            /*if (!file_exists($spkFilePath)) {
                $spk = $pdf;
                $spk->loadHtml($this->renderView('@__main__/public/user/order/print/spk.html.twig', $data));
                $spk->setPaper($paperSize, $paperOrientation);
                $spk->render();
                $fs->appendToFile($spkFilePath, $spk->output());
            }*/

            // Merge pdfs into single file
            $merger = new Merger(new TcpdiDriver());
            $merger->addFile($labelFilePath); // Label
            $merger->addFile($bastFilePath); // BAST

            if (file_exists($receiptFilePath)) {
                $merger->addFile($receiptFilePath); // Receipt
            }

            if (file_exists($spkFilePath)) {
                $merger->addFile($spkFilePath); // SPK
            }

            //if (isset($data['order']['o_sharedId']) && !empty($data['order']['o_sharedId'])) {
            //    $parts = explode('-', $data['order']['o_sharedId']);
            //    $randomCode = $parts[1] ?? StaticHelper::secureRandomCode();
            //} else {
            //    $randomCode = StaticHelper::secureRandomCode();
            //}

            $fileName = 'negotiation.pdf';
            //$filePath = sprintf('%s/orders/%s/%s', $this->getParameter('upload_dir_path'), $randomCode, $fileName);
            $output = $merger->merge();

            //if (!file_exists($filePath)) {
            //    $fs->appendToFile($filePath, $output);
            //}
        } elseif ($type === 'minutes_of_negotiation') {
            $data['batch'] = 5;

            switch ($data['batch']) {
                case 5:
                    $paperSize = 'A1';
                    break;
                case 4:
                    $paperSize = 'A2';
                    break;
                case 3:
                    $paperSize = 'A3';
                    break;
                default:
                    $paperSize = 'A4';
                    break;
            }

            $pdf = new Dompdf($options);
            $pdf->loadHtml($this->renderView('@__main__/public/user/order/print/negotiation.html.twig', $data));
            $pdf->setPaper($paperSize, 'landscape');
            $pdf->render();

            $output = $pdf->output();
        } else {
            $requestData = json_decode($this->getRequest()->getContent(), true);
            //convert image to base64
            $sellerSignature = !is_null($data['seller']['user']) ? $this->sftpUploader->imageToBase64($data['seller']['user']->getUserSignature()) : null;
            $sellerStamp = !is_null($data['seller']['user']) ? $this->sftpUploader->imageToBase64($data['seller']['user']->getUserStamp()) : null;
            $ppkSignature = !is_null($data['data_ppk']) ? $this->sftpUploader->imageToBase64($data['data_ppk']->getUserSignature()) : null;
            $ppkStamp = !is_null($data['data_ppk']) ? $this->sftpUploader->imageToBase64($data['data_ppk']->getUserStamp()) : null;
            $shippingImages = [];
            foreach ($data['order']['o_shippedFiles'] as $image) {
                $shippingImages[] = $this->sftpUploader->imageToBase64('uploads/'. $image['os_filepath']);
            }

            if($type == "bast") {
                $imageUrl = 'uploads/' . $data['order']['o_state_img'];
                $data['fotoResi'] = $this->sftpUploader->imageToBase64($imageUrl);
                $data['shippingImages'] = $shippingImages;
                $data['ppkStamp'] = $ppkStamp;
                $data['ppkSignature'] = $ppkSignature;
                $data['sellerStamp'] = $sellerStamp;
                $data['sellerSignature'] = $sellerSignature;
            }else if($type == "label"){
                // label tidak perlu data 
            }else if($type == "invoice"){
                // invoice tidak perlu data
            }else if($type == "spk"){
                $data['ppkStamp'] = $ppkStamp;
                $data['ppkSignature'] = $ppkSignature;
                $data['sellerStamp'] = $sellerStamp;
                $data['sellerSignature'] = $sellerSignature;
            }else if($type == "spk_non_ttd"){
                $data['ppkStamp'] = $ppkStamp;
                $data['ppkSignature'] = $ppkSignature;
                $data['sellerStamp'] = $sellerStamp;
                $data['sellerSignature'] = $sellerSignature;
            }else if($type == "performa_invoice"){
                $data['sellerStamp'] = $sellerStamp;
                $data['sellerSignature'] = $sellerSignature;
            }else if($type == "receipt_ls"){
                $data['ppkStamp'] = $ppkStamp;
                $data['ppkSignature'] = $ppkSignature;
                $data['treasurerStamp'] = $requestData['treasurer_stamp'] ?? "";
                $data['treasurerSignature'] = $ppkSignature;
                $data['sellerStamp'] = $sellerStamp;
                $data['sellerSignature'] = $sellerSignature;
            }else if($type == "bapd"){
                $data['shippingImages'] = $shippingImages;
                $data['ppkStamp'] = $ppkStamp;
                $data['ppkSignature'] = $ppkSignature;
                $data['sellerStamp'] = $sellerStamp;
                $data['sellerSignature'] = $sellerSignature;
            }else if($type == "bast_ls"){
                $data['shippingImages'] = $shippingImages;
                $data['ppkStamp'] = $ppkStamp;
                $data['ppkSignature'] = $ppkSignature;
                $data['sellerStamp'] = $sellerStamp;
                $data['sellerSignature'] = $sellerSignature;
            }else if($type == "spk_new" || $type == "spk_new_ls"){
                $data['ppkStamp'] = $ppkStamp;
                $data['ppkSignature'] = $ppkSignature;
                $data['sellerStamp'] = $sellerStamp;
                $data['sellerSignature'] = $sellerSignature;
            }else if($type == "bapd_ls"){
                $data['shippingImages'] = $shippingImages;
                $data['ppkStamp'] = $ppkStamp;
                $data['ppkSignature'] = $ppkSignature;
                $data['sellerStamp'] = $sellerStamp;
                $data['sellerSignature'] = $sellerSignature;
            }else if($type == "surat-pengiriman-parsial"){
                $data['ppkStamp'] = $ppkStamp;
                $data['ppkSignature'] = $ppkSignature;
                $data['sellerStamp'] = $sellerStamp;
                $data['sellerSignature'] = $sellerSignature;
            }

            // dd($data);
            $html = $this->renderView(sprintf('@__main__/public/user/order/print/%s.html.twig', $type), $data);
            $pdf = new Dompdf($options);
            $pdf->loadHtml($html);
            $pdf->setPaper($paperSize, $paperOrientation);
            $pdf->render();

            if (isset($parameters['stream']) && $parameters['stream'] === true) {
                $pdf->stream($fileName, ['Attachment' => false]);
                exit;
            }

            $output = $pdf->output();
        }

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename=' . str_replace('.pdf', '', $fileName),
        ]);
    }

    private function handlePrintWord(string $type, array $data, array $parameters = []): Response
    {
        $fileName = 'document.docx';
        $font = isset($parameters['font']) && !empty($parameters['font']) ? $parameters['font'] : 'Arial';
        $paperSize = isset($parameters['paper_size']) && !empty($parameters['paper_size']) ? $parameters['paper_size'] : 'A4';
        $paperOrientation = isset($parameters['paper_orientation']) && !empty($parameters['paper_orientation']) ? $parameters['paper_orientation'] : 'portrait';

        $options = new Options();
        $options->set('defaultFont', $font);

        if (isset($parameters['enable_remote']) && $parameters['enable_remote'] === true) {
            $options->set('isRemoteEnabled', true);
        }

        if ($type === 'negotiation') {
            $fs = new Filesystem();
            // $phpWord = new PhpWord();
            // $section = $phpWord->addSection();

            $word = new Dompdf($options);

            $orderId = $data['order']['o_id'];
            $dirPath = __DIR__ . '/../../../var/word/negotiation';
            $uploadPath = $this->getParameter('upload_dir_path');
            $labelFilePath = $dirPath . '/' . $orderId . '/label.docx';
            $bastFilePath = $dirPath . '/' . $orderId . '/tanda_terima.docx';
            //$receiptFilePath = $dirPath.'/'.$orderId.'/receipt.docx';
            //$spkFilePath = $dirPath.'/'.$orderId.'/spk.docx';
            $receiptFilePath = $uploadPath . '/' . $data['order']['o_receiptFile'];
            $spkFilePath = $uploadPath . '/' . $data['order']['o_workOrderLetterFile'];

            if (!file_exists($labelFilePath)) {
                $label = $word;
                $label->loadHtml($this->renderView('@__main__/public/user/order/print/label.html.twig', $data));
                $label->setPaper($paperSize, $paperOrientation);
                $label->render();
                $fs->appendToFile($labelFilePath, $label->output());
            }

            if (!file_exists($bastFilePath)) {
                $bast = $word;
                $bast->loadHtml($this->renderView('@__main__/public/user/order/print/bast.html.twig', $data));
                $bast->setPaper($paperSize, $paperOrientation);
                $bast->render();
                $fs->appendToFile($bastFilePath, $bast->output());
            }

            /*if (!file_exists($receiptFilePath)) {
                $receipt = $word;
                $receipt->loadHtml($this->renderView('@__main__/public/user/order/print/receipt.html.twig', $data));
                $receipt->setPaper($paperSize, $paperOrientation);
                $receipt->render();
                $fs->appendToFile($receiptFilePath, $receipt->output());
            }*/

            /*if (!file_exists($spkFilePath)) {
                $spk = $pdf;
                $spk->loadHtml($this->renderView('@__main__/public/user/order/print/spk.html.twig', $data));
                $spk->setPaper($paperSize, $paperOrientation);
                $spk->render();
                $fs->appendToFile($spkFilePath, $spk->output());
            }*/

            // Merge words into single file
            $merger = new Merger(new TcpdiDriver());
            $merger->addFile($labelFilePath); // Label
            $merger->addFile($bastFilePath); // BAST

            if (file_exists($receiptFilePath)) {
                $merger->addFile($receiptFilePath); // Receipt
            }

            if (file_exists($spkFilePath)) {
                $merger->addFile($spkFilePath); // SPK
            }

            //if (isset($data['order']['o_sharedId']) && !empty($data['order']['o_sharedId'])) {
            //    $parts = explode('-', $data['order']['o_sharedId']);
            //    $randomCode = $parts[1] ?? StaticHelper::secureRandomCode();
            //} else {
            //    $randomCode = StaticHelper::secureRandomCode();
            //}

            $fileName = 'negotiation.docx';
            //$filePath = sprintf('%s/orders/%s/%s', $this->getParameter('upload_dir_path'), $randomCode, $fileName);
            $output = $merger->merge();

            //if (!file_exists($filePath)) {
            //    $fs->appendToFile($filePath, $output);
            //}
        } elseif ($type === 'minutes_of_negotiation') {
            $data['batch'] = 5;

            switch ($data['batch']) {
                case 5:
                    $paperSize = 'A1';
                    break;
                case 4:
                    $paperSize = 'A2';
                    break;
                case 3:
                    $paperSize = 'A3';
                    break;
                default:
                    $paperSize = 'A4';
                    break;
            }

            $word = new Dompdf($options);
            $word->loadHtml($this->renderView('@__main__/public/user/order/print/negotiation.html.twig', $data));
            $word->setPaper($paperSize, 'landscape');
            $word->render();

            $output = $word->output();
        } else {
            // 'label', 'invoice', 'receipt', 'spk'
            $fileName = $type . '.docx';
            $request = $this->getRequest();
            // dd($data);
            // // header( "Content-Type: application/vnd.openxmlformats-officedocument.wordprocessing‌​ml.document" );
            header("Content-Type: application/vnd.ms-word");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header('Content-Disposition: attachment; filename=' . $fileName);

            //set
            $phpWord = new PhpWord();

            if ($type == 'bast') {
                $this->wordBast($data, $phpWord);
            } else if ($type == 'label') {
                $this->wordLabel($data, $phpWord);
            } else if ($type == 'invoice') {
                $this->wordInvoice($data, $phpWord);
            } else if ($type == 'receipt') {
                $this->wordReceipt($data, $phpWord);
            } else if ($type == 'spk') {
                $this->wordSPK($data, $phpWord);
            }

            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save("php://output");

        }

        return new Response($output, 200, [
            'Content-Type' => "application/vnd.ms-word",
            'Content-Disposition' => 'attachment; filename=' . $fileName,
        ]);
    }

    private function generateQRISPayment(string $billNumber, string $amount, LoggerInterface $logger, ?Qris $qris = null): array
    {
        $qrisClient = new QRISClient();
        $qrisClient->setRequestParameters([
            'billNumber' => $billNumber,
            'amount' => $amount,
        ]);
        
        try {
            $response = $qrisClient->execute();
            $logger->error('QRIS renew/generate response!', $response);

            if (!$response['error']) {
                $qrFactory = $this->get(QrCodeGenerator::class);

                if (isset($response['data']['errorCode']) && in_array($response['data']['errorCode'], ['IB-1009', 'IB-0500'])) {
                    $QRISPaymentData = ['error' => true];
                } else {
                    $QRISPaymentData = $response['data'];
                    $QRISPaymentData['qrImage'] = $qrFactory->dataUri($response['data']['qrValue']);

                    if ($qris === null) {
                        $qris = new Qris();
                        $qris->setInvoice($billNumber);
                        $qris->setBillNumber($QRISPaymentData['billNumber']);
                        $qris->setAmount($QRISPaymentData['amount']);
                        $qris->setTotalAmount($QRISPaymentData['totalAmount']);
                        $qris->setNmid($QRISPaymentData['nmid']);
                        $qris->setMerchantName($QRISPaymentData['merchantName']);
                        $qris->setProductCode($QRISPaymentData['productCode']);
                    }

                    $qris->setQrValue($QRISPaymentData['qrValue']);
                    $qris->setQrImage($QRISPaymentData['qrImage']);
                    $qris->setQrStatus('Belum Terbayar');
                    $qris->setCreatedDate(date('Y-m-d H:i:s'));
                    $qris->setExpiredDate(date('Y-m-d H:i:s', strtotime($QRISPaymentData['expiredDate'])));

                    $em = $this->getEntityManager();
                    $em->persist($qris);
                    $em->flush();
                }
            } else {
                $QRISPaymentData = ['error' => true];
            }
        } catch (Exception $e) {
            $QRISPaymentData = ['error' => true];

            $logger->error(sprintf('QRIS exception handler from %s!', __METHOD__), ['message' => $e->getMessage()]);
        }

        return $QRISPaymentData;
    }

    private function generateVAPayment(VirtualAccount $va, LoggerInterface $logger): array
    {
        try {
            $wsClient = new WSClientBPD();
            $response = $wsClient->billInsertion([
                'id' => $va->getBillNumber(),
                'name' => $va->getName(),
                'nominal' => $va->getAmount(),
                'note_1' => $va->getInvoice(),
                'note_2' => $va->getReferenceId(),
                'note_3' => $va->getTransactionId(),
            ]);
        } catch (Exception $e) {
            $response = [
                'status' => false,
                'code' => '99',
                'message' => $e->getMessage(),
            ];
        }

        if ($response['status'] && $response['code'] === '00') {
            $VAPaymentData = $response['data'];

            $va->setRecordId($VAPaymentData[0]['recordId']);
            $va->setPaidDate($VAPaymentData[0]['tgl_upd']);
            $va->setPaidStatus($VAPaymentData[0]['sts_bayar']);
            $va->setKdUser($VAPaymentData[0]['kd_user']);
            $va->setResponse(json_encode($VAPaymentData));

            $em = $this->getEntityManager();
            $em->persist($va);
            $em->flush();

            $logger->error('VA generate success on order shared page!', $response);
        } else {
            $VAPaymentData = ['error' => true];

            $logger->error('VA generate error on order shared page!', $response);
        }

        return $VAPaymentData;
    }

    public function setOrderToProcessed($orderId)
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();

        $origin = $request->request->get('origin', 'invalid');

        $response = [
            'status' => false
        ];

        if ($origin === $this->sellerOrigin) {
            $orderRepository = $this->getRepository(Order::class);
            /** @var User $user */
            $authSeller = (int)$this->getUser()->getId();

            try {
                $order = $orderRepository->find($orderId);
                $sellerId = (int)$order->getSeller()->getUser()->getId();

                if ($order->getStatus() === 'paid' && $sellerId === $authSeller) {
                    $previousOrderValues = clone $order;

                    $order->setStatus('processed');
                    $order->setStatusChangeTime();

                    $em = $this->getEntityManager();
                    $em->persist($order);
                    $em->flush();

                    $this->logOrder($em, $previousOrderValues, $order, $this->getUser());

                    $response['status'] = true;
                }
            } catch (Exception $exception) {
            }
        }

        return $this->view('', $response, 'json');
    }

    public function wordSPK($data, $phpWord)
    {
        $section = $phpWord->addSection();
        $section_2 = $phpWord->addSection();

        $phpWord->addParagraphStyle('text-center', ['align' => 'center', 'spaceBefore' => 100, 'spaceAfter' => 100]);
        $phpWord->addParagraphStyle('text-right', ['align' => 'right', 'spaceBefore' => 100, 'spaceAfter' => 100]);
        $phpWord->addParagraphStyle('space-text', ['spaceBefore' => 100, 'spaceAfter' => 100]);
        $border = ['borderColor' => '000', 'borderSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER];
        $no_border = ['borderColor' => 'fff', 'borderSize' => 0, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER];

        $order = $data['order'];
        $seller = $data['seller'];
        $formatter = new NumberFormatter('id', NumberFormatter::SPELLOUT);

        $nego_price = 0;
        $nego_batch = 0;
        $sub_total_nego_price = 0;
        $sub_total_ppn_price = 0;
        $nego_shipping = 0;

        if (count($order['o_negotiatedProducts']) > 0) {
            foreach ($order['o_negotiatedProducts'] as $key => $temp_nego_data) {
                $nego_batch = $temp_nego_data['on_batch'];
                $nego_shipping = $temp_nego_data['on_negotiatedShippingPrice'];
            }
        }

        $section->addImage(
            'https://balimall.id/dist/img/balimall.png?v124',
            [
                'width' => 80,
                'height' => 40,
                'wrappingStyle' => 'behind'
            ]
        );


        $table = $section->addTable();
        $table->addRow();
        $table->addCell(2917, [
            'borderLeftColor' => '000', 'borderLeftSize' => 2,
            'borderRightColor' => '000', 'borderRightSize' => 2,
            'borderTopColor' => '000', 'borderTopSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ]);
        $table->addCell(2917, $border)->addText("Nama Instansi", [], 'space-text');
        if (empty($order['o_institution_name'])) {
            $instansi = "-";
        } else {
            $instansi = $order['o_institution_name'];
        }
        $table->addCell(2917, $border)->addText($instansi, [], 'space-text');

        $table->addRow();
        $table->addCell(2917, [
            'borderLeftColor' => '000', 'borderLeftSize' => 2,
            'borderRightColor' => '000', 'borderRightSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ]);
        $table->addCell(2917, $border)->addText("ID Transaksi", [], 'space-text');
        $table->addCell(2917, $border)->addText($order['o_id'], [], 'space-text');

        $table->addRow();
        $table->addCell(2917, [
            'borderLeftColor' => '000', 'borderLeftSize' => 2,
            'borderRightColor' => '000', 'borderRightSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ]);
        $table->addCell(2917, $border)->addText("Tanggal Transaksi", [], 'space-text');
        $table->addCell(2917, $border)->addText($data['order_date_day'], [], 'space-text');

        $table->addRow();
        $table->addCell(2917, [
            'borderLeftColor' => '000', 'borderLeftSize' => 2,
            'borderRightColor' => '000', 'borderRightSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("Surat Pesanan", [], 'space-text');
        $table->addCell(2917, $border)->addText("Nama Paket Pekerjaan", [], 'space-text');
        $table->addCell(2917, $border)->addText($order['o_jobPackageName'], [], 'space-text');

        $table->addRow();
        $table->addCell(2917, [
            'borderLeftColor' => '000', 'borderLeftSize' => 2,
            'borderRightColor' => '000', 'borderRightSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ]);
        $table->addCell(2917, $border)->addText("Tahun Anggaran", [], 'space-text');
        $table->addCell(2917, $border)->addText($order['o_fiscalYear'], [], 'space-text');

        $table->addRow();
        $table->addCell(2917, [
            'borderLeftColor' => '000', 'borderLeftSize' => 2,
            'borderRightColor' => '000', 'borderRightSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ]);
        $table->addCell(2917, $border)->addText("Sumber Dana", [], 'space-text');
        $table->addCell(2917, $border)->addText($order['o_sourceOfFund'], [], 'space-text');

        $table->addRow();
        $table->addCell(2917, [
            'borderLeftColor' => '000', 'borderLeftSize' => 2,
            'borderRightColor' => '000', 'borderRightSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ]);
        $table->addCell(2917, $border)->addText("Pagu Anggaran", [], 'space-text');
        $table->addCell(2917, $border)->addText(
            number_format(str_replace(".", "", intval($order['o_budgetCeiling'])), 0, '', '.'),
            [],
            'space-text'
        );

        $table->addRow();
        $table->addCell(8750, [
            'borderColor' => '000',
            'borderSize' => 2,
            'gridSpan' => 3,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("Surat pesanan ini mulai berlaku efektif terhitung sejak tanggal ditetapkan dan penyelesaian keseluruhan pekerjaan sebagaimana dimaksud dalam surat pesanan ini.", [], 'space-text');

        $hari_kerja = str_replace('titik', 'koma', $formatter->format($order['o_executionTime']));
        $table->addRow();
        $table->addCell(8750, [
            'borderColor' => '000',
            'borderSize' => 2,
            'gridSpan' => 3,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("Waktu Pelaksanaan Pekerjaan : " . $order['o_executionTime'] . "(" . ucfirst($hari_kerja) . ") Hari Kerja", [], 'space-text');

        $section->addText("<w:br/> Rincian Pekerjaan", ['size' => 10], 'text-center');

        $table_rincian = $section->addTable();
        $table_rincian->addRow();
        $table_rincian->addCell(500, $border)->addText("No", ['size' => 10, 'bold' => true], 'text-center');
        $table_rincian->addCell(2500, $border)->addText("Nama Barang/Jasa", ['size' => 10, 'bold' => true], 'text-center');
        $table_rincian->addCell(500, $border)->addText("Qty", ['size' => 10, 'bold' => true], 'text-center');
        $table_rincian->addCell(800, $border)->addText("Satuan", ['size' => 10, 'bold' => true], 'text-center');
        $table_rincian->addCell(2075, $border)->addText("Harga Satuan", ['size' => 10, 'bold' => true], 'text-center');
        $table_rincian->addCell(2375, $border)->addText("Jumlah", ['size' => 10, 'bold' => true], 'text-center');

        $initial_with_shipping = true;
        foreach ($order['o_products'] as $index => $product) {
            foreach ($order['o_negotiatedProducts'] as $key => $product_nego) {
                if (($product_nego['p_id'] == $product['p_id']) && ($product_nego['on_batch'] == $nego_batch)) {
                    $nego_price = number_format(str_replace(".", "", intval($product_nego['on_negotiatedPrice'] * $product['op_quantity'])), 0, '', '.');
                    $sub_total_nego_price = $sub_total_nego_price + ($product_nego['on_negotiatedPrice'] * $product['op_quantity']);

                    if ($initial_with_shipping) {
                        $sub_total_ppn_price = $sub_total_ppn_price + ($product_nego['on_taxNominalPrice'] * $product['op_quantity']) + $product_nego['on_taxNominalShipping'];
                    } else {
                        $sub_total_ppn_price = $sub_total_ppn_price + ($product_nego['on_taxNominalPrice'] * $product['op_quantity']);
                    }
                    $initial_with_shipping = false;
                    $nego_shipping = $product_nego['on_negotiatedShippingPrice'];

                    $no = $index + 1;
                    $satuan = $product['p_unit'] != null && $product['p_unit'] != "" ? $product['p_unit'] : 'Unit';
                    $table_rincian->addRow();
                    $table_rincian->addCell(500, $border)->addText($no, [], 'text-center');
                    $table_rincian->addCell(2500, $border)->addText($product['p_name']);
                    $table_rincian->addCell(500, $border)->addText($product['op_quantity'], [], 'text-center');
                    $table_rincian->addCell(800, $border)->addText($satuan, [], 'text-center');
                    $table_rincian->addCell(2075, $border)->addText(number_format(str_replace(".", "", intval($product_nego['on_negotiatedPrice'])), 0, '', '.'), [], 'text-right');
                    $table_rincian->addCell(2375, $border)->addText($nego_price, [], 'text-right');
                }
            }
        }


        $table_rincian->addRow();
        $table_rincian->addCell(5800, [
            'borderColor' => '000',
            'borderSize' => 2,
            'gridSpan' => 5,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("Biaya Kirim (Rp)  ", [], 'text-right');
        $table_rincian->addCell(1300, $border)->addText(number_format(str_replace(".", "", intval($nego_shipping)), 0, '', '.'), [], 'text-right');

        $table_rincian->addRow();
        $table_rincian->addCell(5800, [
            'borderColor' => '000',
            'borderSize' => 2,
            'gridSpan' => 5,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("Total PPN (Rp)  ", [], 'text-right');
        $table_rincian->addCell(1300, $border)->addText(number_format(str_replace(".", "", intval($sub_total_ppn_price)), 0, '', '.'), [], 'text-right');

        $order_grand_total = $sub_total_nego_price + $nego_shipping + $sub_total_ppn_price;
        $table_rincian->addRow();
        $table_rincian->addCell(5800, [
            'borderColor' => '000',
            'borderSize' => 2,
            'gridSpan' => 5,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("Total (Rp)  ", [], 'text-right');
        $table_rincian->addCell(1300, $border)->addText(number_format(str_replace(".", "", intval($order_grand_total)), 0, '', '.'), [], 'text-right');

        $table_rincian->addRow();
        $table_rincian->addCell(7100, [
            'borderColor' => '000',
            'borderSize' => 2,
            'gridSpan' => 6,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("INSTRUKSI KE PENYEDIA : ");

        $table_rincian->addRow();
        $table_rincian->addCell(7100, [
            'borderColor' => '000',
            'borderSize' => 2,
            'gridSpan' => 6,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("Penagihan hanya dapat dilakukan setelah penyelesaian pekerjaan yang diperintahkan dalam surat pesanan ini dan dibuktikan dengan Tanda Terima");

        $table_ttd = $section_2->addTable();
        $table_ttd->addRow();
        $table_ttd->addCell(2917);
        $table_ttd->addCell(2917);
        $table_ttd->addCell(2917)->addText("Denpasar, " . $data['order_date'], ['size' => 10, 'bold' => true], 'text-center');

        $table_ttd->addRow();
        $table_ttd->addCell(2917)->addText("Merchant", ['size' => 10, 'bold' => true], 'text-center');
        $table_ttd->addCell(2917);
        $table_ttd->addCell(2917)->addText("Pemesan", ['size' => 10, 'bold' => true], 'text-center');

        $table_ttd->addRow();
        $table_ttd->addCell(2917)->addText("<w:br/><w:br/><w:br/><w:br/><w:br/><w:br/>");
        $table_ttd->addCell(2917);
        $table_ttd->addCell(2917);

        $table_ttd->addRow();
        $pt_name = $order['s_name'] != null && $order['s_name'] != "" ? $order['s_name'] : "";
        $table_ttd->addCell(2917)->addText($pt_name, ['size' => 10, 'bold' => true], 'text-center');
        $table_ttd->addCell(2917);
        $table_ttd->addCell(2917);

        $table_ttd->addRow();
        $table_ttd->addCell(2917)->addText($order['s_firstName'] . ' ' . $order['s_lastName'], ['size' => 10, 'bold' => true], 'text-center');
        $table_ttd->addCell(2917);
        $table_ttd->addCell(2917)->addText($order['u_ppName'], ['size' => 10, 'bold' => true], 'text-center');
    }

    public function wordReceipt($data, $phpWord)
    {
        $section = $phpWord->addSection();
        $translator = $this->getTranslator();

        $phpWord->addParagraphStyle('text-center', ['align' => 'center', 'spaceAfter' => 100]);
        $phpWord->addParagraphStyle('text-right', ['align' => 'right', 'spaceBefore' => 100]);
        $border = ['borderColor' => '000', 'borderSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER];
        $no_border = ['borderColor' => 'fff', 'borderSize' => 0, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER];

        $order = $data['order'];
        $seller = $data['seller'];
        $formatter = new NumberFormatter('id', NumberFormatter::SPELLOUT);

        $section->addImage(
            'https://balimall.id/dist/img/balimall.png?v124',
            [
                'width' => 80,
                'height' => 40,
                'wrappingStyle' => 'behind'
            ]
        );

        $table_header = $section->addTable([
            'borderLeftColor' => '000',
            'borderLeftSize' => 2,
            'borderRightColor' => '000',
            'borderRightSize' => 2,
            'borderTopColor' => '000',
            'borderTopSize' => 2,
            'borderBottomColor' => '000',
            'borderBottomSize' => 2,
        ]);
        $table_header->addRow();
        $table_header->addCell(4200, ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::TOP])->addText("Nomor : " . $order['o_id'], ['size' => 10]);
        $cell_tahun = $table_header->addCell(4550, ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER]);
        $cell_tahun->addText("Tahun Anggaran                : " . $order['o_fiscalYear'], ['size' => 10, 'spaceAfter' => 50]);
        $cell_tahun->addText("Sumber Dana                    : " . $order['o_sourceOfFund'], ['size' => 10, 'spaceAfter' => 50]);
        $cell_tahun->addText("Kode Rekening Anggaran : " . $order['o_budget_account'], ['size' => 10, 'spaceAfter' => 50]);
        $cell_tahun->addText("Nama Bank                       : BPD CAB. RENON", ['size' => 10, 'spaceAfter' => 50]);
        $cell_tahun->addText("No Rekening                     : 010.01.11.000290", ['size' => 10, 'spaceAfter' => 50]);

        $section->addText('<w:br/> Kwitansi Pembayaran <w:br/> ', ['size' => 14, 'bold' => true], 'text-center');

        $table_kwitansi = $section->addTable();
        $table_kwitansi->addRow();
        $table_kwitansi->addCell(5100, $no_border)->addText("Sudah Terima Dari", ['size' => 10], ['spaceBefore' => 200]);
        $table_kwitansi->addCell(100, $no_border)->addText(" :     ", ['size' => 10]);
        $terima_dari = $order['o_work_unit_name'] != null && $order['o_work_unit_name'] != "" ? $order['o_work_unit_name'] : "";
        $table_kwitansi->addCell(3550, $no_border)->addText($translator->trans('label.' . $order['o_treasurer_type']) . ' ' . $terima_dari . '<w:br/>' . $order['o_institution_name'], ['size' => 10], ['spaceBefore' => 200]);

        $table_kwitansi->addRow();
        $table_kwitansi->addCell(5100, $no_border)->addText("Banyaknya Uang", ['size' => 10], ['spaceBefore' => 200]);
        $table_kwitansi->addCell(100, $no_border)->addText(" :     ", ['size' => 10]);
        $banyak_uang = number_format(str_replace(".", "", intval($order['o_total'] + $order['o_shippingPrice'])), 0, '', '.');
        $table_kwitansi->addCell(3550, $border)->addText('Rp. ' . $banyak_uang, ['size' => 10], ['spaceBefore' => 200]);

        $table_kwitansi->addRow();
        $table_kwitansi->addCell(5100, $no_border)->addText("Untuk Pembayaran", ['size' => 10], ['spaceBefore' => 200]);
        $table_kwitansi->addCell(100, $no_border)->addText(" :     ", ['size' => 10]);
        $table_kwitansi->addCell(3550, $no_border)->addText($order['o_jobPackageName'], ['size' => 10], ['spaceBefore' => 200]);
        $section->addText("<w:br/>");

        $table_terbilang = $section->addTable([
            'borderTopColor' => '666',
            'borderTopSize' => 10,
            'borderBottomColor' => '666',
            'borderBottomSize' => 10,
            'cellSpacing' => 100
        ]);

        $terbilang = str_replace('titik', 'koma', $formatter->format($order['o_total'] + $order['o_shippingPrice']));
        $table_terbilang->addRow();
        $table_terbilang->addCell(1500, $no_border)->addText("Terbilang", ['size' => 12, 'bold' => true], ['spaceBefore' => 200]);
        $table_terbilang->addCell(7250, $border)->addText("  " . ucfirst($terbilang), ['size' => 12, 'bold' => true], ['spaceBefore' => 200]);
        $section->addText("<w:br/> <w:br/>");

        $table_ttd = $section->addTable();
        $table_ttd->addRow();
        $table_ttd->addCell(2916.6, $no_border)->addText("Mengetahui", ['size' => 10], 'text-center');
        $table_ttd->addCell(2916.6, $no_border)->addText("Lunas Dibayar", ['size' => 10], 'text-center');
        $table_ttd->addCell(2916.6, $no_border)->addText($order['o_city'] . ", " . $data['date'], ['size' => 10], 'text-center');

        $table_ttd->addRow();
        $work_unit = $order['o_work_unit_name'] != null && $order['o_work_unit_name'] != "" ? $order['o_work_unit_name'] : "";
        $table_ttd->addCell(2916.6, $no_border)->addText($translator->trans('label.' . $order['o_ppk_type']) . '<w:br/>' . $work_unit . '<w:br/>' . $order['o_institution_name'], ['size' => 10], 'text-center');
        $table_ttd->addCell(2916.6, $no_border)->addText($translator->trans('label.' . $order['o_treasurer_type']) . "<w:br/>" . $work_unit . '<w:br/>' . $order['o_institution_name'], ['size' => 10], 'text-center');
        $cell_menerima = $table_ttd->addCell(2916.6, $no_border);
        $order_name = $order['s_name'] != null && $order['s_name'] != "" ? $order['s_name'] : "";
        $cell_menerima->addText("Yang menerima", ['size' => 10], 'text-center');
        $cell_menerima->addText($order_name, ['size' => 10], 'text-center');

        $table_ttd->addRow();
        $table_ttd->addCell(8750, [
            'borderColor' => 'fff', 'gridSpan' => 3, 'borderSize' => 0, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("<w:br/><w:br/><w:br/><w:br/>");

        $table_ttd->addRow();
        $table_ttd->addCell(2916.6, $no_border)->addText($order['o_ppk_name'] . "<w:br/>NIP. " . $order['o_ppk_nip'], ['size' => 10], 'text-center');
        $table_ttd->addCell(2916.6, $no_border)->addText($order['o_treasurer_name'] . "<w:br/>NIP. " . $order['o_treasurer_nip'], ['size' => 10], 'text-center');
        $table_ttd->addCell(2916.6, $no_border)->addText($seller['user']->getFirstName() . ' ' . $seller['user']->getLastName(), ['size' => 10], 'text-center');
    }

    public function wordInvoice($data, $phpWord)
    {
        $section = $phpWord->addSection();
        $section_2 = $phpWord->addSection();

        $phpWord->addParagraphStyle('text-center', ['align' => 'center', 'spaceAfter' => 100]);
        $phpWord->addParagraphStyle('text-right', ['align' => 'right', 'spaceBefore' => 100]);
        $border = ['borderColor' => '000', 'borderSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER];
        $no_border = ['borderColor' => 'fff', 'borderSize' => 0, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER];

        $orderRepository = $this->getRepository(Order::class);
        $voucherRepository = $this->getRepository(VoucherUsedLog::class);
        $translator = $this->getTranslator();

        $order = $data['order'];
        $seller = $data['seller'];
        $order_vouchers_list = $voucherRepository->getVouchersForOrderBySharedIdGroupByVoucherId($order['o_sharedId']);
        $order_vouchers_lists = $voucherRepository->getVouchersForOrderBySharedIdGroupByVoucherId($order['o_sharedId'], 'no');
        $order_groups = $orderRepository->getOrderRelatedBySharedId($order['o_sharedId'], $order['o_id']);

        //Halaman
        $section->addText('Invoice No. ', ['size' => 12, 'bold' => true]);
        $section->addText('Surat Tagihan <w:br/> <w:br/>', ['size' => 14, 'bold' => true]);

        if ($order['u_role'] == 'ROLE_USER_GOVERNMENT') {
            $kepada = $order['o_work_unit_name'] != null && $order['o_work_unit_name'] != "" ? $order['o_work_unit_name'] : "-";
        } else {
            $kepada = $order['o_name'];
        }
        $table_row = $section->addTable();
        $table_row->addRow();
        $table_row->addCell(3250, ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])->addText("Dari :", ['size' => 10, 'bold' => true]);
        $table_row->addCell(3250, ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])->addText("Kepada :", ['size' => 10, 'bold' => true]);
        $table_row->addCell(2250, ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])->addText("Nomor Order : " . $order['o_id'], ['size' => 10, 'bold' => true]);
        $table_row->addRow();
        $table_row->addCell(3250, ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])->addText($order['s_name'], ['size' => 10]);
        $table_row->addCell(3250, ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])->addText($translator->trans('label.' . $order['o_ppk_type']) . " " . $kepada, ['size' => 10]);
        $table_row->addCell(2250, ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])->addText("", ['size' => 10]);
        $table_row->addRow();
        $table_row->addCell(3250, ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])->addText($order['s_address'], ['size' => 10]);
        $kldi = $order['o_institution_name'] != null && $order['o_institution_name'] != "" ? $order['o_institution_name'] : "-";
        $table_row->addCell(3250, ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])->addText($kldi, ['size' => 10]);
        $table_row->addCell(2250, ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])->addText("", ['size' => 10]);

        $section->addText('<w:br/>');


        $table_bank = $section->addTable();
        $table_bank->addRow();
        $table_bank->addCell(2916.6, [
            'borderTopColor' => '000',
            'borderTopSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("Batas Pembayaran :", ['size' => 10, 'bold' => true]);
        $table_bank->addCell(2916.6, [
            'borderTopColor' => '000',
            'borderTopSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("Bank :", ['size' => 10, 'bold' => true]);
        $table_bank->addCell(2916.6, [
            'borderTopColor' => '000',
            'borderTopSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("No. Account : ", ['size' => 10, 'bold' => true]);

        $table_bank->addRow();
        $table_bank->addCell(2916.6, [
            'borderBottomColor' => '000',
            'borderBottomSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("1 x 24 jam", ['size' => 10]);
        $table_bank->addCell(2916.6, [
            'borderBottomColor' => '000',
            'borderBottomSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("BPD CAB. RENON", ['size' => 10]);
        $table_bank->addCell(2916.6, [
            'borderBottomColor' => '000',
            'borderBottomSize' => 2,
            'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText("010.01.11.000290", ['size' => 10]);

        $section->addText('<w:br/>');

        $table_penting = $section->addTable();
        $table_penting->addRow();
        $cell_penting = $table_penting->addCell(8750, [
            'bgColor' => 'ffebeb'
        ])->addTextRun();
        $cell_penting->addText('<w:br/>');
        $cell_penting->addText("     Penting! <w:br/>", ['size' => 10, 'bold' => true, 'color' => 'red']);
        $cell_penting->addText("     Nomor rekening diatas hanya berlaku untuk pembayaran pesanan dengan nomor order", ['size' => 8, 'color' => '003300']);
        $cell_penting->addText(" " . $order['o_id'], ['size' => 8, 'color' => 'red']);
        $cell_penting->addText('<w:br/>');

        $section->addText('<w:br/>');

        $table_item = $section->addTable([
            'cellSpacing' => 30,
            'borderLeftColor' => '000',
            'borderLeftSize' => 2,
            'borderRightColor' => '000',
            'borderRightSize' => 2,
            'borderTopColor' => '000',
            'borderTopSize' => 2,
            'borderBottomColor' => '000',
            'borderBottomSize' => 2,
        ]);
        $table_item->addRow();
        $table_item->addCell(500, $border)->addText("No", ['size' => 10, 'bold' => true], 'text-center');
        $table_item->addCell(2500, $border)->addText("Nama Barang/Jasa", ['size' => 10, 'bold' => true], 'text-center');
        $table_item->addCell(500, $border)->addText("Qty", ['size' => 10, 'bold' => true], 'text-center');
        $table_item->addCell(800, $border)->addText("Satuan", ['size' => 10, 'bold' => true], 'text-center');
        $table_item->addCell(2075, $border)->addText("Harga Satuan", ['size' => 10, 'bold' => true], 'text-center');
        $table_item->addCell(2375, $border)->addText("Jumlah", ['size' => 10, 'bold' => true], 'text-center');

        $shipping_price = 0;
        $with_tax = false;
        $is_pkp_store = $order['s_pkp'] == '1';
        $order_tax_nominal = 0;

        foreach ($order['o_products'] as $key => $product) {
            $shipping_price = $product['op_priceShippingNegotiation'];
            if ($product['op_withTax'] == true) {
                $with_tax = true;
            }
            $no = $key + 1;
            $table_item->addRow();
            $table_item->addCell(500, $border)->addText($no, ['size' => 10], 'text-center');
            $table_item->addCell(2500, $border)->addText($product['p_name'], ['size' => 10]);
            $table_item->addCell(500, $border)->addText($product['op_quantity'], ['size' => 10], 'text-center');
            $satuan = $product['p_unit'] != null && $product['p_unit'] != "" ? $product['p_unit'] : "Unit";
            $table_item->addCell(800, $border)->addText($satuan, ['size' => 10], 'text-center');
            $table_item->addCell(2075, $border)->addText(number_format(str_replace(".", "", intval($product['op_price'])), 0, '', '.'), ['size' => 10], 'text-right');
            $table_item->addCell(2375, $border)->addText(number_format(str_replace(".", "", intval($product['op_totalPrice'])), 0, '', '.'), ['size' => 10], 'text-right');
            $order_tax_nominal = $order_tax_nominal + $product['op_taxNominal'];
        }

        if ($order['u_role'] == 'ROLE_USER_GOVERNMENT' && $is_pkp_store && $with_tax) {
            $order_total = $order['o_total'] - $order_tax_nominal;
        } else {
            $order_total = $order['o_total'];
        }

        $table_item->addRow();
        $table_item->addCell(500, $no_border);
        $table_item->addCell(2500);
        $table_item->addCell(500);
        $table_item->addCell(800);
        $table_item->addCell(2075, $no_border)->addText("Jumlah", ['size' => 10]);
        $table_item->addCell(2375, $no_border)->addText(number_format(str_replace(".", "", intval($order_total)), 0, '', '.'), ['size' => 10], 'text-right');

        if ($order['u_role'] == 'ROLE_USER_GOVERNMENT') {
            if ($is_pkp_store) {
                $order_tax_nominal = $order_tax_nominal + round(intval($shipping_price) * 0.1, 1);
            } else {
                $order_tax_nominal = 0;
            }
            $biaya_kirim = number_format(str_replace(".", "", intval($shipping_price)), 0, '', '.');
        } else {
            if ($is_pkp_store) {
                $order_tax_nominal = $order_tax_nominal + ($order['o_shippingPrice'] - round($order['o_shippingPrice'] / (0.1 + 1), 1));
                $biaya_kirim = number_format(str_replace(".", "", intval(round($order['o_shippingPrice'] / (0.1 + 1), 1))), 0, '', '.');
            } else {
                $order_tax_nominal = 0;
                $biaya_kirim = number_format(str_replace(".", "", intval($order['o_shippingPrice'])), 0, '', '.');
            }
        }

        $table_item->addRow();
        $table_item->addCell(500, $no_border);
        $table_item->addCell(2500);
        $table_item->addCell(500);
        $table_item->addCell(800);
        $table_item->addCell(2075, $no_border)->addText("Biaya Kirim", ['size' => 10]);
        $table_item->addCell(2375, $no_border)->addText($biaya_kirim, ['size' => 10], 'text-right');

        if ($order['u_role'] == 'ROLE_USER_GOVERNMENT' && $order['s_pkp'] == "0") {
            $order_tax_nominal = 0;
        }

        $table_item->addRow();
        $table_item->addCell(500, $no_border);
        $table_item->addCell(2500);
        $table_item->addCell(500);
        $table_item->addCell(800);
        $table_item->addCell(2075, $no_border)->addText("PPN", ['size' => 10]);
        $table_item->addCell(2375, $no_border)->addText(number_format(str_replace(".", "", intval($order_tax_nominal)), 0, '', '.'), ['size' => 10], 'text-right');

        if ($order['u_role'] != 'ROLE_USER_GOVERNMENT') {
            if (count($order_groups) > 0) {
                foreach ($order_groups as $key => $order_group) {
                    if ($is_pkp_store && $with_tax) {
                        $order_group_total = $order_group['o_total'] + round($order_group['o_total'] * 0.1, 1) + $order_group['o_shippingPrice'];
                        $order_tax = $order_tax + round($order_group['o_total'] * 0.1, 1);
                    } else {
                        $order_group_total = $order_group['o_total'] + $order_group['o_shippingPrice'];
                    }

                    $table_item->addRow();
                    $table_item->addCell(3000, ['borderColor' => '000', 'borderSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER, 'gridSpan' => 3])->addText("Transaksi Terkait", ['size' => 10]);
                    $table_item->addCell(2075, ['borderColor' => '000', 'borderSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER, 'gridSpan' => 2])->addText($order_group['o_invoice'], ['size' => 10, 'bold' => true]);
                    $table_item->addCell(2375, ['borderColor' => '000', 'borderSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])->addText(number_format(str_replace(".", "", intval($order_group_total)), 0, '', '.'), ['size' => 10], 'text-right');
                }
            }

            if (count($order_vouchers_list) > 0) {
                $total_order_amount = 0;
                $total_voucher_amount = 0;
                $o_lists = [];
                $v_lists = [];

                foreach ($order_vouchers_lists as $key => $voucher_list) {
                    if (in_array($voucher_list['vul_orderId'], $o_lists)) {
                        $total_order_amount = $total_order_amount + $voucher_list['vul_orderAmount'];
                        $o_lists = array_merge($o_lists, $voucher_list['vul_orderId']);
                    }

                    if (in_array($voucher_list['v_code'], $v_lists)) {
                        $total_voucher_amount = $total_voucher_amount + $voucher_list['v_amount'];
                        $table_item->addRow();
                        $table_item->addCell(3000, ['borderColor' => '000', 'borderSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER, 'gridSpan' => 3])->addText("Voucher", ['size' => 10]);
                        $table_item->addCell(2075, ['borderColor' => '000', 'borderSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER, 'gridSpan' => 2])->addText($voucher_list['v_code'], ['size' => 10, 'bold' => true]);
                        $table_item->addCell(2375, ['borderColor' => '000', 'borderSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])->addText(number_format(str_replace(".", "", intval($voucher_list['v_amount'])), 0, '', '.'), ['size' => 10], 'text-right');
                        $v_lists = array_merge($v_lists, $voucher_list['v_code']);
                    }
                }

                if ($is_pkp_store && $with_tax) {
                    $order_tax = $order_tax + round($order['o_total'] * 0.1, 1);
                    $total_order_amount = $total_order_amount + $order_tax;
                }
                $grand_total = $total_order_amount - $total_voucher_amount;
                $grand_total_value = number_format(str_replace(".", "", intval($grand_total)), 0, '', '.');
                if ($grand_total < 0) {
                    $grand_total_value = 0;
                }
                $total_value = $grand_total_value;
            } else {
                if ($is_pkp_store && $with_tax) {
                    $order_grand_total = $order['o_total'] + round($order['o_shippingPrice'] / (0.1 + 1), 1) + $order_tax_nominal;
                } else {
                    $order_grand_total = $order['o_total'] + $order['o_shippingPrice'] + $order_tax_nominal;
                }
                $total_value = number_format(str_replace(".", "", intval($order_grand_total)), 0, '', '.');
            }
        } else {
            $order_grand_total = $order['o_total'] + $order['o_shippingPrice'];
            $total_value = number_format(str_replace(".", "", intval($order_grand_total)), 0, '', '.');
        }

        $table_item->addRow();
        $table_item->addCell(500, $border)->addText("Total", ['size' => 10, 'bold' => true]);
        $table_item->addCell(5875, ['borderColor' => '000', 'borderSize' => 2, 'gridSpan' => 4])->addText("");
        $table_item->addCell(2375, $border)->addText($total_value, ['size' => 10], 'text-right');


        $table_intruksi = $section_2->addTable();
        $table_intruksi->addRow();
        $table_intruksi->addCell(8750)->addText("Instruksi!", ['size' => 10, 'bold' => true, 'color' => 'green']);

        $table_intruksi->addRow();
        $table_intruksi->addCell(8750)->addText("Mohon upload bukti pembayaran Anda di halaman detail pembayaran supaya kami dapat memverifikasi pembayaran yang telah Anda lakukan.", ['size' => 10, 'color' => '000']);

        $table_intruksi->addRow();
        $table_intruksi->addCell(8750)->addText("Mohon upload bukti pemotongan pajak pada aplikasi bela pengadaan balimall.id, jika pada transaksi ini merchant dikenakan potongan pajak <w:br/>", ['size' => 10, 'color' => '000']);


        $table_ttd = $section_2->addTable();
        $table_ttd->addRow();
        $table_ttd->addCell(4400)->addText("");
        $cell_date = $table_ttd->addCell(4350);
        $cell_date->addText($seller['city'] . ', ' . $data['date'], ['size' => 10, 'bold' => true], 'text-center');
        $cell_date->addText("Merchant <w:br/> <w:br/> <w:br/> <w:br/> <w:br/>", ['size' => 10, 'bold' => true], 'text-center');
        $cell_date->addText($order['s_name'], ['size' => 10, 'bold' => true], 'text-center');
        $cell_date->addText($order['s_firstName'] . ' ' . $order['s_lastName'], ['size' => 10, 'bold' => true], 'text-center');
    }

    public function wordBast($data, $phpWord)
    {
        $section = $phpWord->addSection();
        $section_2 = $phpWord->addSection();

        //Style
        $phpWord->addParagraphStyle('text-center', ['align' => 'center', 'spaceAfter' => 100]);
        $phpWord->addParagraphStyle('text-right', ['align' => 'right', 'spaceBefore' => 100]);
        $border = ['borderColor' => '000', 'borderSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER];


        //Halaman 1
        $section->addImage(
            'https://balimall.id/dist/img/balimall.png?v124',
            [
                'width' => 80,
                'height' => 40,
                'wrappingStyle' => 'behind'
            ]
        );
        $section->addText('<w:br/> <w:br/> TANDA TERIMA <w:br/> ', ['size' => 16, 'bold' => true], 'text-center');

        $table_nama = $section->addTable();
        $table_nama->addRow();
        $table_nama->addCell(1750)->addText("Nama Pengadaan");
        $table_nama->addCell(100)->addText(": ");
        $table_nama->addCell(6900)->addText($data['order']['o_jobPackageName'] . '<w:br/>');
        $table_nama->addRow();
        $table_nama->addCell(1750)->addText("Satuan Kerja");
        $table_nama->addCell(100)->addText(": ");
        if (empty($data['order']['o_institution_name']) && empty($data['order']['o_work_unit_name'])) {
            $table_nama->addCell(6900)->addText("-");
        } else {
            $table_nama->addCell(1750)->addText($data['order']['o_institution_name']);
            $table_nama->addRow();
            $table_nama->addCell(1750)->addText("");
            $table_nama->addCell(100)->addText("");
            $table_nama->addCell(6900)->addText($data['order']['o_work_unit_name']);
        }


        $table_row = $section->addTable(['cellMargin' => 50]);
        $table_row->addRow();
        $table_row->addCell(100, $border)->addText("No", ['bold' => true], 'text-center');
        $table_row->addCell(2800, $border)->addText("Nama Barang/Jasa", ['bold' => true], 'text-center');
        $table_row->addCell(800, $border)->addText("Jumlah Dikirim", ['bold' => true], 'text-center');
        $table_row->addCell(800, $border)->addText("Satuan", ['bold' => true], 'text-center');
        $table_row->addCell(800, $border)->addText("Jumlah Diterima", ['bold' => true], 'text-center');
        $cell_1 = $table_row->addCell(800, $border);
        $cell_1->addText("Kondisi", ['bold' => true], 'text-center');
        $cell_1->addText("(coret yang salah)", ['size' => 6], ['bold' => true], 'text-center');
        $table_row->addCell(1300, $border)->addText("Harga Satuan (Rp)", ['bold' => true], 'text-center');
        $table_row->addCell(1300, $border)->addText("Jumlah (Rp)", ['bold' => true], 'text-center');


        $nego_price = 0;
        $nego_batch = 0;
        $sub_total_nego_price = 0;
        $sub_total_ppn_price = 0;
        $nego_shipping = 0;
        $order = $data['order'];
        $seller = $data['seller'];

        if (count($order['o_negotiatedProducts']) > 0) {
            foreach ($order['o_negotiatedProducts'] as $key => $temp_nego_data) {
                $nego_batch = $temp_nego_data['on_batch'];
            }
        }

        $initial_with_shipping = true;

        foreach ($order['o_products'] as $key_1 => $product) {
            foreach ($order['o_negotiatedProducts'] as $key_2 => $product_nego) {
                if (($product_nego['p_id'] == $product['p_id']) && ($product_nego['on_batch'] == $nego_batch)) {
                    $nego_price = ($product_nego['on_negotiatedPrice'] * $product['op_quantity']);
                    $sub_total_nego_price = $sub_total_nego_price + ($product_nego['on_negotiatedPrice'] * $product['op_quantity']);

                    if ($initial_with_shipping) {
                        $sub_total_ppn_price = $sub_total_ppn_price + ($product_nego['on_taxNominalPrice'] * $product['op_quantity']) + $product_nego['on_taxNominalShipping'];
                    } else {
                        $sub_total_ppn_price = $sub_total_ppn_price + ($product_nego['on_taxNominalPrice'] * $product['op_quantity']);
                    }
                    $initial_with_shipping = false;
                    $nego_shipping = $product_nego['on_negotiatedShippingPrice'];

                    $no = $key_1 + 1;
                    $satuan = $product['p_unit'] != null && $product['p_unit'] != "" ? $product['p_unit'] : 'Unit';
                    // dd(str_replace(".","",$product_nego['on_negotiatedPrice']));
                    $table_row->addRow();
                    $table_row->addCell(100, $border)->addText($no, [], 'text-center');
                    $table_row->addCell(2800, $border)->addText($product['p_name']);
                    $table_row->addCell(800, $border)->addText($product['op_quantity'], [], 'text-center');
                    $table_row->addCell(800, $border)->addText($satuan, [], 'text-center');
                    $table_row->addCell(800, $border)->addText("", [], 'text-center');
                    $table_row->addCell(800, $border)->addText("Baik / Buruk", [], 'text-center');
                    $table_row->addCell(1300, $border)->addText(number_format(str_replace(".", "", intval($product_nego['on_negotiatedPrice'])), 0, '', '.'), [], 'text-right');
                    $table_row->addCell(1300, $border)->addText(number_format(str_replace(".", "", intval($nego_price)), 0, '', '.'), [], 'text-right');
                }
            }
        }

        $table_row->addRow();
        $table_row->addCell(100);
        $table_row->addCell(2800);
        $table_row->addCell(800);
        $table_row->addCell(800);
        $table_row->addCell(800);
        $table_row->addCell(800);
        $table_row->addCell(1300, ['borderLeftColor' => '000', 'borderLeftSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])
            ->addText("Jumlah (Rp)", ['bold' => true]);
        $table_row->addCell(1300, ['borderRightColor' => '000', 'borderRightSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])
            ->addText(number_format(str_replace(".", "", intval($sub_total_nego_price)), 0, '', '.'), [], 'text-right');

        $table_row->addRow();
        $table_row->addCell(100);
        $table_row->addCell(2800);
        $table_row->addCell(800);
        $table_row->addCell(800);
        $table_row->addCell(800);
        $table_row->addCell(800);
        $table_row->addCell(1300, ['borderLeftColor' => '000', 'borderLeftSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])
            ->addText("Biaya Kirim (Rp)", ['bold' => true]);
        $table_row->addCell(1300, ['borderRightColor' => '000', 'borderRightSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])
            ->addText(number_format(str_replace(".", "", intval($nego_shipping)), 0, '', '.'), [], 'text-right');

        $table_row->addRow();
        $table_row->addCell(100);
        $table_row->addCell(2800);
        $table_row->addCell(800);
        $table_row->addCell(800);
        $table_row->addCell(800);
        $table_row->addCell(800);
        $table_row->addCell(1300, ['borderLeftColor' => '000', 'borderLeftSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])
            ->addText("PPN (Rp)", ['bold' => true]);
        $table_row->addCell(1300, ['borderRightColor' => '000', 'borderRightSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])
            ->addText(number_format(str_replace(".", "", intval($sub_total_ppn_price)), 0, '', '.'), [], 'text-right');

        $order_grand_total = $sub_total_nego_price + $nego_shipping + $sub_total_ppn_price;
        $table_row->addRow();
        $table_row->addCell(100);
        $table_row->addCell(2800);
        $table_row->addCell(800);
        $table_row->addCell(800);
        $table_row->addCell(800);
        $table_row->addCell(800);
        $table_row->addCell(1300, ['borderLeftColor' => '000', 'borderLeftSize' => 2, 'borderBottomColor' => '000', 'borderBottomSize' => 2, 'bgColor' => 'f5f5f5', 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])
            ->addText("TOTAL", ['bold' => true]);
        $table_row->addCell(1300, ['borderRightColor' => '000', 'borderRightSize' => 2, 'borderBottomColor' => '000', 'borderBottomSize' => 2, 'bgColor' => 'f5f5f5', 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER])
            ->addText(number_format(str_replace(".", "", intval($order_grand_total)), 0, '', '.'), [], 'text-right');

        //Halaman 2
        $section_2->addText('Tanda Terima ini berfungsi sebagai bukti serah terima.');
        $section_2->addText('Demikian Tanda Terima ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana seharusnya. <w:br/> <w:br/> <w:br/>');

        $table_ttd = $section_2->addTable();
        $table_ttd->addRow();
        $cell_ttd_1 = $table_ttd->addCell(4375)->addText('Yang Menyerahkan', [], 'text-center');
        $cell_ttd_2 = $table_ttd->addCell(4375)->addText('Yang Menerima', [], 'text-center');
        $seller_name = $seller['name'] != null && $seller['name'] != "" ? $seller['name'] : "";
        $institution_name = $order['o_institution_name'] != null && $order['o_institution_name'] != "" ? $order['o_institution_name'] : "";
        $work_unit_name = $order['o_work_unit_name'] != null && $order['o_work_unit_name'] != "" ? $order['o_work_unit_name'] : "";

        $table_ttd->addRow();
        $cell_ttd_1 = $table_ttd->addCell(4375)->addText($seller_name, [], 'text-center');
        $cell_ttd_2 = $table_ttd->addCell(4375)->addText($work_unit_name . '<w:br/>' . $institution_name, [], 'text-center');

        $table_ttd->addRow();
        $cell_ttd_1 = $table_ttd->addCell(4375);
        $cell_ttd_2 = $table_ttd->addCell(4375);
        $table_ttd->addRow();
        $cell_ttd_1 = $table_ttd->addCell(4375);
        $cell_ttd_2 = $table_ttd->addCell(4375);
        $table_ttd->addRow();
        $cell_ttd_1 = $table_ttd->addCell(4375)->addText('(_________________________)', [], 'text-center');
        $cell_ttd_2 = $table_ttd->addCell(4375)->addText('(_________________________)', [], 'text-center');
    }

    public function wordLabel($data, $phpWord)
    {
        $section = $phpWord->addSection();


        $order = $data['order'];
        $seller = $data['seller'];
        $qty = $data['qty'];
        $order_date = $data['order_date'];
        // Style
        $phpWord->addParagraphStyle('text-center', ['align' => 'center', 'spaceAfter' => 100]);
        $phpWord->addParagraphStyle('text-right', ['align' => 'right']);
        $phpWord->addParagraphStyle('left-tab', ['tabs' => [new \PhpOffice\PhpWord\Style\Tab('left', 2000)]]);
        $border = ['borderColor' => '000', 'borderSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER];
        // $cellRowSpan = ['vMerge' => 'restart'];
        // $cellRowContinue = ['vMerge' => 'continue'];
        // $cellColSpan = ['gridSpan' => 2];
        // Halaman

        $section->addText('<w:br/> <w:br/> TEMPELKAN DILUAR PAKET <w:br/> ', ['size' => 16, 'bold' => true], 'text-center');

        $table_row1 = $section->addTable();
        $table_row1->addRow();
        $table_row1->addCell(4375, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'borderTopColor' => '000', 'borderTopSize' => 25, 'borderBottomColor' => '000', 'borderBottomSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addImage(
            'https://balimall.id/dist/img/balimall.png?v124',
            [
                'width' => 80,
                'height' => 40,
                'wrappingStyle' => 'behind'
            ]
        );
        $cell_fragile = $table_row1->addCell(4375, [
            'borderRightColor' => '000', 'borderRightSize' => 25, 'borderTopColor' => '000', 'borderTopSize' => 25, 'borderBottomColor' => '000', 'borderBottomSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ]);
        $cell_fragile->addImage(
            'https://balimall.id/dist/img/label-fragile.jpg?v124',
            [
                'width' => 60,
                'height' => 20,
                'wrappingStyle' => 'behind',
                'alignment' => 'right'
            ]
        );
        $cell_fragile->addText("TANGANI DENGAN HATI-HATI  ", [], 'text-right');

        $table_row2 = $section->addTable();
        $table_row2->addRow();
        $table_row2->addCell(8750, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'borderRightColor' => '000', 'borderRightSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText('<w:br/>   No. Pesanan     : ' . $order['o_id'], ['size' => 11]);
        $table_row2->addRow();
        $table_row2->addCell(8750, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'borderRightColor' => '000', 'borderRightSize' => 25, 'borderBottomColor' => '000', 'borderBottomSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText('   Tanggal Pesan : ' . $order_date, ['size' => 11]);

        $table_row3 = $section->addTable();
        $table_row3->addRow();
        $table_row3->addCell(8750, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'borderRightColor' => '000', 'borderRightSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER, 'gridSpan' => 2
        ])->addText('<w:br/> Penerima : <w:br/>', ['size' => 10, 'bold' => true], ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER]);

        $table_row3->addRow();
        $table_row3->addCell(1500, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText('Nama     :   ', ['size' => 10], 'text-right');
        $table_row3->addCell(7250, [
            'borderRightColor' => '000', 'borderRightSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText(' ' . $order['u_firstName'] . ' ' . $order['u_lastName'], ['size' => 10]);

        $table_row3->addRow();
        $table_row3->addCell(1500, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText('Alamat   :   ', ['size' => 10], 'text-right');
        $table_row3->addCell(7250, [
            'borderRightColor' => '000', 'borderRightSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText(' ' . $order['o_address'], ['size' => 10]);

        $table_row3->addRow();
        $table_row3->addCell(1500, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'borderBottomColor' => '000', 'borderBottomSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText('No. Telp :   ', ['size' => 10], 'text-right');
        $table_row3->addCell(7250, [
            'borderRightColor' => '000', 'borderRightSize' => 25, 'borderBottomColor' => '000', 'borderBottomSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText(' ' . $order['o_phone'], ['size' => 10]);


        $table_row4 = $section->addTable();
        $table_row4->addRow();
        $table_row4->addCell(8750, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'borderRightColor' => '000', 'borderRightSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER, 'gridSpan' => 2
        ])->addText('<w:br/> Pengirim : <w:br/>', ['size' => 10, 'bold' => true], ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER]);

        $table_row4->addRow();
        $table_row4->addCell(2350, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText('Nama Merchant     :   ', ['size' => 10], 'text-right');
        $table_row4->addCell(6400, [
            'borderRightColor' => '000', 'borderRightSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText(' ' . $seller['name'], ['size' => 10]);

        $table_row4->addRow();
        $table_row4->addCell(2350, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText('Alamat Merchant   :   ', ['size' => 10], 'text-right');
        $table_row4->addCell(6400, [
            'borderRightColor' => '000', 'borderRightSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText(' ' . $seller['address'], ['size' => 10]);

        $table_row4->addRow();
        $table_row4->addCell(2350, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText('No. Telp Merchant :   ', ['size' => 10], 'text-right');
        $table_row4->addCell(6400, [
            'borderRightColor' => '000', 'borderRightSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText(' ' . $seller['user']->getPhoneNumber(), ['size' => 10]);

        $table_row4->addRow();
        $table_row4->addCell(2350, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'borderBottomColor' => '000', 'borderBottomSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText('Jumlah Barang       :   ', ['size' => 10], 'text-right');
        $table_row4->addCell(6400, [
            'borderRightColor' => '000', 'borderRightSize' => 25, 'borderBottomColor' => '000', 'borderBottomSize' => 2, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER
        ])->addText(' ' . $qty, ['size' => 10]);


        $table_row5 = $section->addTable();
        $table_row5->addRow();
        $table_row5->addCell(8750, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'borderRightColor' => '000', 'borderRightSize' => 25, 'vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER, 'gridSpan' => 2
        ])->addText('<w:br/> Perhatian : <w:br/>', ['size' => 10, 'bold' => true], ['vAlign' => \PhpOffice\PhpWord\SimpleType\VerticalJc::CENTER]);

        $table_row5->addRow();
        $table_row5->addCell(500, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25,
        ])->addText(' -. ', ['size' => 10], 'text-right');
        $table_row5->addCell(8250, [
            'borderRightColor' => '000', 'borderRightSize' => 25,
        ])->addText(' Periksa barang dengan teliti saat paket diterima', ['size' => 10]);

        $table_row5->addRow();
        $table_row5->addCell(500, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25,
        ])->addText(' -. ', ['size' => 10], 'text-right');
        $table_row5->addCell(8250, [
            'borderRightColor' => '000', 'borderRightSize' => 25,
        ])->addText(' Pastikan jumlah dan barang sesuai dengan pesanan', ['size' => 10]);

        $table_row5->addRow();
        $table_row5->addCell(500, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25,
        ])->addText(' -. ', ['size' => 10], 'text-right');
        $table_row5->addCell(8250, [
            'borderRightColor' => '000', 'borderRightSize' => 25,
        ])->addText(' Segera lakukan Konfirmasi Terima dihalaman akun Anda setelah barang diperiksa', ['size' => 10]);

        $table_row5->addRow();
        $table_row5->addCell(500, [
            'borderLeftColor' => '000', 'borderLeftSize' => 25, 'borderBottomColor' => '000', 'borderBottomSize' => 25,
        ])->addText(' -. ', ['size' => 10], 'text-right');
        $table_row5->addCell(8250, [
            'borderRightColor' => '000', 'borderRightSize' => 25, 'borderBottomColor' => '000', 'borderBottomSize' => 25,
        ])->addText(' Jika terdapat ketidaksesuaian produk yang dikirim dengan pesanan, silahkan lakukan prosedur komplain melalui aplikasi bela pengadaan balimall.id', ['size' => 10]);
    }

    public function printDisbursement(int $orderId): Response
    {

        $orderRepository = $this->getRepository(Order::class);
        $disbursementRepository = $this->getRepository(Disbursement::class);
        $order = $orderRepository->find($orderId);
        $disbursement = $disbursementRepository->findOneBy(['orderId' => $order->getId()]);

        if (!$order instanceof Order || !$disbursement instanceof Disbursement) {
            throw new NotFoundHttpException();
        }

        if ($this->getUser() && ($this->getUser()->getId() !== $order->getSeller()->getUser()->getId())) {
            throw new NotFoundHttpException();
        }

        $data = [
            'id' => $disbursement->getId(),
            'total_product_price' => $disbursement->getTotalProductPrice(),
            'product_fee' => $disbursement->getProductFee(),
            'ppn' => $disbursement->getPpn(),
            'pph' => $disbursement->getPph(),
            'management_fee' => $disbursement->getManagementFee(),
            'other_fee' => $disbursement->getOtherFee(),
            'bank_fee' => $disbursement->getBankFee(),
            'status' => $disbursement->getStatus(),
            'total' => $disbursement->getTotal(),
            'invoice' => $order->getInvoice(),
            'products' => $orderRepository->getOrderProducts($order->getId()),
            'merchant' => $order->getSeller()->getName(),
            'persentase_ppn' => $disbursement->getPersentasePpn(),
            'persentase_pph' => $disbursement->getPersentasePph(),
            'persentase_bank' => $disbursement->getPersentaseBank(),
            'persentase_management' => $disbursement->getPersentaseManagement(),
            'persentase_other' => $disbursement->getPersentaseOther(),
            'payment_proof' => $disbursement->getPaymentProof(),
            'rekening_name' => $disbursement->getRekeningName(),
            'bank_name' => $disbursement->getBankName(),
            'nomor_rekening' => $disbursement->getNomorRekening(),
            'order_shipping_price' => $disbursement->getOrderShippingPrice(),
            's_umkm_category' => $order->getSeller()->getUmkmCategory(),
        ];

        $fileName = sprintf('%s_%s.pdf', 'disbursement', $order->getInvoice());

        $font = 'Times New Roman';
        $paperSize = 'A4';
        $paperOrientation = 'portrait';

        $options = new Options();
        $options->set('defaultFont', $font);

        $pdf = new Dompdf($options);

        $file = $this->renderView('@__main__/email/disbursement_detail.html.twig', ['data' => $data]);

        $agreement = $pdf;
        $agreement->loadHtml($file);
        $agreement->setPaper($paperSize, $paperOrientation);
        $agreement->render();

        return new Response($agreement->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename=' . $fileName,
        ]);
    }

    public function cancelOrder()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $logger = $this->get('logger');
        $sharedInvoice = $request->request->get('shared_invoice', 'invalid');
        $origin = $request->request->get('origin', 'invalid');
        $user = $this->getUser();
        // dd($user, $sharedInvoice, $origin);
        $response = [
            'status' => false,
        ];

        if ($origin === $this->buyerOrigin && $user->getRole() === 'ROLE_USER_GOVERNMENT') {
            if ($sharedInvoice !== 'invalid') {
                $repository = $this->getRepository(Order::class);

                try {
                    $sharedInvoice = filter_var($sharedInvoice, FILTER_SANITIZE_STRING);

                    $orders = $repository->findBy([
                        'sharedId' => $sharedInvoice,
                        'status' => 'processed',
                        'negotiationStatus' => 'finish'
                    ]);

                    if (count($orders) > 0) {
                        $em = $this->getEntityManager();

                        foreach ($orders as $order) {
                            if ((int)$order->getBuyer()->getId() !== (int)$user->getId()) {
                                throw new Exception("invalid owner");
                            }

                            $prevOrder = clone $order;

                            $order->setCancellationStatus('requested');

                            $this->logOrder($em, $prevOrder, $order, $user);

                            $em->persist($order);
                            $em->flush();
                        }

                        $response = [
                            'status' => true,
                        ];
                    }
                } catch (\Exception $e) {
                    $logger->error('Cancel Order Error', $e->getMessage());
                }
            }
        }

        if ($origin === $this->sellerOrigin && $user->getRole() === 'ROLE_USER_SELLER') {
            if ($sharedInvoice !== 'invalid') {
                $repository = $this->getRepository(Order::class);

                try {
                    $sharedInvoice = filter_var($sharedInvoice, FILTER_SANITIZE_STRING);

                    $orders = $repository->findBy([
                        'sharedId' => $sharedInvoice,
                        'status' => 'processed',
                        'negotiationStatus' => 'finish',
                        'cancellationStatus' => 'requested'
                    ]);

                    if (count($orders) > 0) {
                        $em = $this->getEntityManager();

                        foreach ($orders as $order) {
                            if ((int)$order->getSeller()->getUser()->getId() !== (int)$user->getId()) {
                                throw new Exception("invalid owner");
                            }

                            $prevOrder = clone $order;

                            $order->setCancellationStatus('accepted');
                            $order->setStatus('cancel');

                            $this->logOrder($em, $prevOrder, $order, $order->getSeller()->getUser());

                            $em->persist($order);
                            $em->flush();
                        }

                        $response = [
                            'status' => true,
                        ];
                    }
                } catch (\Exception $e) {
                    $logger->error('Cancel Order Error', $e->getMessage());
                }
            }
        }

        return $this->view('', $response, 'json');
    }

    public function approveOrder($id)
    {

        $user = $this->getUser();
        $route = 'user_order_detail';

        $repository = $this->getRepository(Order::class);
        $order = $repository->find($id);

        if ($order instanceof Order) {
            if (
                $user->getLkppInstanceId() === $order->getBuyer()->getLkppInstanceId() &&
                $user->getLkppRole() === 'PPK' &&
                $order->getStatus() === 'pending_approve'
            ) {
                $em = $this->getEntityManager();

                $order->setStatus('confirmed');
                $em->persist($order);
                $em->flush();
            }
        }

        return $this->redirectToRoute($route, ['id' => $id]);
    }

    public function ppk_approve($id, LoggerInterface $logger)
    {
        $repository = $this->getRepository(Order::class);
        $order      = $repository->find($id);
        $translator = $this->getTranslator();
        BreadcrumbService::add(['label' => $this->getTranslation('label.approve_order')]);

        $request = $this->getRequest();

        if ($request->isMethod('POST')) {
            if ($request->request->get('nip_ppk', null) == $order->getPpkNip()) {
                $em = $this->getEntityManager();
                $order->setIsApprovedPPK(true);
                $order->setStatusApprovePpk('disetujui');
                $em->persist($order);
                $em->flush();

                $status = 'received';

                if ($order->getPpkPaymentMethod() == 'uang_persediaan') {
                    $prevOrderValues = clone $order;
                    $order->setStatus($status);
                    $user_update = $order->getBuyer();
                    $this->logOrder($this->getEntityManager(), $prevOrderValues, $order, $user_update);
                    $status = 'pending_payment';
                    $user_update = $order->getSeller()->getUser();
                }
                $this->setDisbursementProductFee($em, $order);

                $response = $this->setOrderStatus($order, $status);

                if (!empty($order->getUnitName()) && !empty($order->getUnitEmail())) {
                    /**
                     * Send email to pic
                     */
                    try {
                        /** @var BaseMail $mailToSeller */
                        $mailToSeller = $this->get(BaseMail::class);
                        $mailToSeller->setMailSubject('Bmall ' . $order->getInvoice() . '_Approval PPK');
                        $mailToSeller->setMailTemplate('@__main__/email/sent_to_pic.html.twig');
                        $mailToSeller->setMailRecipient($order->getUnitEmail());
                        $mailToSeller->setMailData([
                            'name' => $order->getUnitName(),
                            'invoice' => $order->getInvoice(),
                            'pp' => $order->getName(),
                            'ppk_name' => $order->getPpkName(),
                            'satker' => !empty($order->getWorkUnitName()) ? $order->getWorkUnitName() : $order->getBuyer()->getLkppWorkUnit(),
                            'klpd' => !empty($order->getInstitutionName()) ? $order->getInstitutionName() : $order->getBuyer()->getLkppKLDI(),
                            'payment_method' => $order->getPpkPaymentMethod(),
                            'merchant' => $order->getSeller()->getName(),
                            'status' => 'received',
                            'payment_method' => $order->getPpkPaymentMethod(),
                            'type' => 'pic',
                            'link_login' => getenv('APP_URL') . '/login',
                            'link_confirm' => $this->generateUrl('ppk_approve', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_i' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_st' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'performa_invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_b' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'bast'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_sp' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'spk'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_k' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'receipt'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_bapd' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'bapd'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_spk' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'spk_new'], UrlGeneratorInterface::ABSOLUTE_URL),
                        ]);
                        $mailToSeller->send();
                    } catch (\Throwable $exception) {
                        $this->logger->error('Send Email PIC Throwable', [$exception->getMessage()]);
                    }
                }

                if (!empty($order->getTreasurerName()) && !empty($order->getTreasurerEmail())) {

                    $em = $this->getEntityManager();
                    if (!empty($order->getDokuInvoiceNumber())) {
                        $order->setDokuInvoiceNumber('');
                    }
                    $em->persist($order);
                    $em->flush();
                    /**
                     * Send email to bendahara
                     */
                    try {
                        /** @var BaseMail $mailToSeller */
                        $mailToSeller = $this->get(BaseMail::class);
                        $mailToSeller->setMailSubject('Bmall ' . $order->getInvoice() . '_Payment Process');
                        $mailToSeller->setMailTemplate('@__main__/email/sent_to_pic.html.twig');
                        $mailToSeller->setMailRecipient($order->getTreasurerEmail());
                        $mailToSeller->setMailData([
                            'name' => $order->getTreasurerName(),
                            'invoice' => $order->getInvoice(),
                            'pp' => $order->getName(),
                            'ppk_name' => $order->getPpkName(),
                            'satker' => !empty($order->getWorkUnitName()) ? $order->getWorkUnitName() : $order->getBuyer()->getLkppWorkUnit(),
                            'klpd' => !empty($order->getInstitutionName()) ? $order->getInstitutionName() : $order->getBuyer()->getLkppKLDI(),
                            'merchant' => $order->getSeller()->getName(),
                            'status' => 'received',
                            'payment_method' => $order->getPpkPaymentMethod(),
                            'type' => 'treasurer',
                            'link_login' => getenv('APP_URL') . '/login',
                            'link_confirm' => $this->generateUrl('ppk_approve', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_pay' => $this->generateUrl('treasurer_pay_with_channel', ['id' => $order->getSharedId(), 'channel' => 'doku'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_i' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_st' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'performa_invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_b' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'bast'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_sp' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'spk'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_k' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'receipt'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_bapd' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'bapd'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'link_spk' => $this->generateUrl('document_pic', ['id' => $order->getId(), 'type' => 'spk_new'], UrlGeneratorInterface::ABSOLUTE_URL),
                        ]);
                        $mailToSeller->send();
                    } catch (\Throwable $exception) {
                        $this->logger->error('Send Email Bendahara Throwable', [$exception->getMessage()]);
                    }
                }

                $this->addFlash(
                    'success',
                    $translator->trans('message.success.ppk_order_approved')
                );
            } else {
                $this->addFlash(
                    'warning',
                    $translator->trans('message.info.nip_not_match')
                );
            }
        }
        $section = 'ppk_approve_order';
        return $this->view('@__main__/public/order/ppk_approve.html.twig', [
            'order' => $order,
            'token_id' => $section,
        ]);
    }

    public function exportData($id)
    {
        $request = $this->getRequest();
        $keyword = $request->query->get('keyword', null);
        $status  = $request->query->get('status', null);

        if ($this->getUserStore()) {
            $parameters['seller'] = $this->getUserStore();
        } else {
            $parameters['buyer'] = $this->getUser();
        }

        $parameters['roles'] = ['ROLE_USER_GOVERNMENT'];
        if (!empty($keyword)) {
            $parameters['keywords'] = $keyword;
        }

        if (!empty($status)) {
            $parameters['status'] = $status;
        }

        $repository = $this->getRepository(Order::class);
        $data = $repository->getDataForTable($parameters);
        $url = $this->get('router')->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $package = new UrlPackage($url, new EmptyVersionStrategy());
        $writer = null;

        $number = 1;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValueByColumnAndRow(1, 1, 'No.');
        $sheet->setCellValueByColumnAndRow(2, 1, 'ID');
        $sheet->setCellValueByColumnAndRow(3, 1, 'Invoice');
        $sheet->setCellValueByColumnAndRow(4, 1, 'Shared Invoice');
        $sheet->setCellValueByColumnAndRow(5, 1, 'Status');
        $sheet->setCellValueByColumnAndRow(6, 1, 'Total');
        $sheet->setCellValueByColumnAndRow(7, 1, 'Shipping Amount');
        $sheet->setCellValueByColumnAndRow(8, 1, 'Shipping Courier');
        $sheet->setCellValueByColumnAndRow(9, 1, 'Shipping Service');
        $sheet->setCellValueByColumnAndRow(10, 1, 'Tracking Code');
        $sheet->setCellValueByColumnAndRow(11, 1, 'Buyer Name');
        $sheet->setCellValueByColumnAndRow(12, 1, 'Buyer Email');
        $sheet->setCellValueByColumnAndRow(13, 1, 'Buyer Phone');
        $sheet->setCellValueByColumnAndRow(14, 1, 'Buyer Address');
        $sheet->setCellValueByColumnAndRow(15, 1, 'Buyer City');
        $sheet->setCellValueByColumnAndRow(16, 1, 'Buyer Province');
        $sheet->setCellValueByColumnAndRow(17, 1, 'Buyer Post Code');
        $sheet->setCellValueByColumnAndRow(18, 1, 'Note');
        $sheet->setCellValueByColumnAndRow(19, 1, 'Tax Document Email');
        $sheet->setCellValueByColumnAndRow(20, 1, 'Tax Document Phone');
        $sheet->setCellValueByColumnAndRow(21, 1, 'Tax Document File');
        $sheet->setCellValueByColumnAndRow(22, 1, 'Is B2G Transaction');
        $sheet->setCellValueByColumnAndRow(23, 1, 'Negotiation Status');
        $sheet->setCellValueByColumnAndRow(24, 1, 'Execution Time');
        $sheet->setCellValueByColumnAndRow(25, 1, 'Job Package Name');
        $sheet->setCellValueByColumnAndRow(26, 1, 'Fiscal Year');
        $sheet->setCellValueByColumnAndRow(27, 1, 'Source of Fund');
        $sheet->setCellValueByColumnAndRow(28, 1, 'Budget Ceiling');
        $sheet->setCellValueByColumnAndRow(29, 1, 'BAST File');
        $sheet->setCellValueByColumnAndRow(30, 1, 'Delivery Paper File');
        $sheet->setCellValueByColumnAndRow(31, 1, 'Tax Invoice File');
        $sheet->setCellValueByColumnAndRow(32, 1, 'Invoice File');
        $sheet->setCellValueByColumnAndRow(33, 1, 'Receipt File');
        $sheet->setCellValueByColumnAndRow(34, 1, 'SPK File');
        $sheet->setCellValueByColumnAndRow(35, 1, 'Store Name');
        $sheet->setCellValueByColumnAndRow(36, 1, 'Store Address');
        $sheet->setCellValueByColumnAndRow(37, 1, 'Product Name');
        $sheet->setCellValueByColumnAndRow(38, 1, 'Product Category');
        $sheet->setCellValueByColumnAndRow(39, 1, 'Payment Method');
        $sheet->setCellValueByColumnAndRow(40, 1, 'Shipped Method');
        $sheet->setCellValueByColumnAndRow(41, 1, 'Status Last Changed On');
        $sheet->setCellValueByColumnAndRow(42, 1, 'Created At');
        $sheet->setCellValueByColumnAndRow(43, 1, 'Updated At');
        if (count($data['data']) > 0) {
            foreach ($data['data'] as $item) {
                $status = ucwords(str_replace('_', ' ', $item['o_status']));
                $taxDocumentFile = !empty($item['o_taxDocumentFile']) ? $package->getUrl($item['o_taxDocumentFile']) : null;
                $bastFile = !empty($item['o_bastFile']) ? $package->getUrl($item['o_bastFile']) : null;
                $deliveryPaperFile = !empty($item['o_deliveryPaperFile']) ? $package->getUrl($item['o_deliveryPaperFile']) : null;
                $taxInvoiceFile = !empty($item['o_taxInvoiceFile']) ? $package->getUrl($item['o_taxInvoiceFile']) : null;
                $invoiceFile = !empty($item['o_invoiceFile']) ? $package->getUrl($item['o_invoiceFile']) : null;
                $receiptFile = !empty($item['o_receiptFile']) ? $package->getUrl($item['o_receiptFile']) : null;
                $workOrderLetterFile = !empty($item['o_workOrderLetterFile']) ? $package->getUrl($item['o_workOrderLetterFile']) : null;

                /** @var OrderRepository $orderRepository */
                $orderRepository = $this->getRepository(Order::class);
                $detailOrder = $orderRepository->getOrderProducts($item['o_id']);

                $storeName = $detailOrder[0]['s_name'];
                $storeAddress = $detailOrder[0]['s_address'];
                $productCategory = $detailOrder[0]['pc_name'];
                $productName = $detailOrder[0]['p_name'];

                $sheet->setCellValueByColumnAndRow(1, ($number + 1), $number);
                $sheet->setCellValueByColumnAndRow(2, ($number + 1), $item['o_id']);
                $sheet->setCellValueByColumnAndRow(3, ($number + 1), $item['o_invoice']);
                $sheet->setCellValueByColumnAndRow(4, ($number + 1), $item['o_sharedInvoice']);
                $sheet->setCellValueByColumnAndRow(5, ($number + 1), $status);
                $sheet->setCellValueByColumnAndRow(6, ($number + 1), $item['o_total']);
                $sheet->setCellValueByColumnAndRow(7, ($number + 1), $item['o_shippingPrice']);
                $sheet->setCellValueByColumnAndRow(8, ($number + 1), $item['o_shippingCourier']);
                $sheet->setCellValueByColumnAndRow(9, ($number + 1), $item['o_shippingService']);
                $sheet->setCellValueByColumnAndRow(10, ($number + 1), $item['o_trackingCode']);
                $sheet->setCellValueByColumnAndRow(11, ($number + 1), $item['o_name']);
                $sheet->setCellValueByColumnAndRow(12, ($number + 1), $item['o_email']);
                $sheet->setCellValueByColumnAndRow(13, ($number + 1), $item['o_phone']);
                $sheet->setCellValueByColumnAndRow(14, ($number + 1), $item['o_address']);
                $sheet->setCellValueByColumnAndRow(15, ($number + 1), $item['o_city']);
                $sheet->setCellValueByColumnAndRow(16, ($number + 1), $item['o_province']);
                $sheet->setCellValueByColumnAndRow(17, ($number + 1), $item['o_postCode']);
                $sheet->setCellValueByColumnAndRow(18, ($number + 1), $item['o_note']);
                $sheet->setCellValueByColumnAndRow(19, ($number + 1), $item['o_taxDocumentEmail']);
                $sheet->setCellValueByColumnAndRow(20, ($number + 1), $item['o_taxDocumentPhone']);
                $sheet->setCellValueByColumnAndRow(21, ($number + 1), $taxDocumentFile);
                $sheet->setCellValueByColumnAndRow(22, ($number + 1), $item['o_isB2gTransaction'] ? 'Yes' : 'No');
                $sheet->setCellValueByColumnAndRow(23, ($number + 1), $item['o_negotiationStatus']);
                $sheet->setCellValueByColumnAndRow(24, ($number + 1), $item['o_executionTime']);
                $sheet->setCellValueByColumnAndRow(25, ($number + 1), $item['o_jobPackageName']);
                $sheet->setCellValueByColumnAndRow(26, ($number + 1), $item['o_fiscalYear']);
                $sheet->setCellValueByColumnAndRow(27, ($number + 1), $item['o_sourceOfFund']);
                $sheet->setCellValueByColumnAndRow(28, ($number + 1), $item['o_budgetCeiling']);
                $sheet->setCellValueByColumnAndRow(29, ($number + 1), $bastFile);
                $sheet->setCellValueByColumnAndRow(30, ($number + 1), $deliveryPaperFile);
                $sheet->setCellValueByColumnAndRow(31, ($number + 1), $taxInvoiceFile);
                $sheet->setCellValueByColumnAndRow(32, ($number + 1), $invoiceFile);
                $sheet->setCellValueByColumnAndRow(33, ($number + 1), $receiptFile);
                $sheet->setCellValueByColumnAndRow(34, ($number + 1), $workOrderLetterFile);
                $sheet->setCellValueByColumnAndRow(35, ($number + 1), $storeName);
                $sheet->setCellValueByColumnAndRow(36, ($number + 1), $storeAddress);
                $sheet->setCellValueByColumnAndRow(37, ($number + 1), $productName);
                $sheet->setCellValueByColumnAndRow(38, ($number + 1), $productCategory);
                $sheet->setCellValueByColumnAndRow(39, ($number + 1), !empty($item['o_ppk_payment_method']) ? $this->getParameter('ppk_method_options')[$item['o_ppk_payment_method']] : '');
                $sheet->setCellValueByColumnAndRow(40, ($number + 1), !empty($item['o_shipped_method']) ? $this->getParameter('shipped_method_options')[$item['o_shipped_method']] : '');
                $sheet->setCellValueByColumnAndRow(41, ($number + 1), !empty($item['o_statusChangeTime']) ? $item['o_statusChangeTime']->format('Y-m-d H:i:s') : '-');
                $sheet->setCellValueByColumnAndRow(42, ($number + 1), $item['o_createdAt']->format('Y-m-d H:i:s'));
                $sheet->setCellValueByColumnAndRow(43, ($number + 1), !empty($item['o_updatedAt']) ? $item['o_updatedAt']->format('Y-m-d H:i:s') : '-');


                $number++;
            }
        }

        $writer = new Xlsx($spreadsheet);
        // Create a Temporary file in the system
        $fileName = 'riwayat_transaksi.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($temp_file);

        // Return the excel file as an attachment
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    public function bniPaymentSingle($orderSharedId)
    {
        $orderRepository = $this->getRepository(Order::class);
        $bniRepository = $this->getRepository(Bni::class);
        $userRepository = $this->getRepository(User::class);
        $bniService = $this->get(BniService::class);
        $em = $this->getEntityManager();
        $user = $this->getUser();

        $orders = $orderRepository->findBy([
            'sharedId' => $orderSharedId,
        ]);
        $userPpk = $userRepository->find($orders[0]->getPpkId());
        $satkerData = $this->getRepository(Satker::class)->find($orders[0]->getSatkerId());
        $noVA = getenv('VA_BNI_PREFIX') . getenv('VA_BNI_CLIENT_ID') . $satkerData->getDigitVa();

        $pendingPaymentBni = $bniRepository->findOneBy([
            'va' => $noVA,
            'status' => 'pending'
        ]);

        if ($pendingPaymentBni == null) {
            $userPpk = $userRepository->find($orders[0]->getPpkId());
            // Calculate the amount of the order(s)
            $nominal = (int) round($this->getTotalToBePaidForPayment($orderSharedId, $orders));
            $requestId = $this->generateBniRequestId();
            $digitSatker = $satkerData->getDigitVa();
            $result = $bniService->createVa($nominal, $requestId, $digitSatker, $user);

            if ($result['status']) {
                $data = $result['data'];
                $bni = new Bni();
                $bni->setVa($data['virtual_account']);
                $bni->setAmount($nominal);
                $bni->setStatus('pending');
                $bni->setResponse(json_encode($data));
                $bni->setRequestId($requestId);
                $bni->setType('single');
                $bni->setUser($user);

                $expiredAt = new DateTime(date('c', time() + ((24 * 3600) * 3)), new DateTimeZone('Asia/Makassar'));

                $bni->setExpiredTime($expiredAt);
                $bni->setCreatedAt();

                $em->persist($bni);
                $em->flush();

                foreach ($orders as $key => $order) {
                    $products = $order->getOrderProducts();
                    $pphTreasurer  = $order->getTaxType() == '59' ? (!empty($order->getTreasurerPphNominal()) ? $order->getTreasurerPphNominal() : 0) : 0;
                    $ppnTreasurer  = $order->getTaxType() == '59' ? (!empty($order->getTreasurerPpnNominal()) ? $order->getTreasurerPpnNominal() : 0) : 0;
                    $totalAmount = $order->getTotal() + $order->getShippingPrice() - $pphTreasurer - $ppnTreasurer;

                    $bniDetail = new BniDetail();
                    $bniDetail->setOrderId($order->getId());
                    $bniDetail->setBniTrxId($bni->getId());
                    $bniDetail->setAmount($totalAmount);
                    $bniDetail->setCreatedAt();

                    $em->persist($bniDetail);
                    $em->flush();
                }

                $this->addFlash(
                    'success',
                    'Request pembayaran menggunakan No VA ' . $noVA . ' berhasil.'
                );
            }
        } else {
            $this->addFlash(
                'warning',
                'Terdapat Request yang belum selesai pada No VA ' . $noVA . ', Mohon untuk selesaikan pembayaran untuk dapat melakukan request kembali.'
            );
        }


        return $this->redirectToRoute('user_bnipayment_dashboard');
    }

    function generateUniqueId()
    {
        $min = 100000000000; // Angka minimum (12 digit pertama)
        $max = 999999999999; // Angka maksimum (12 digit terakhir)
        $id = mt_rand($min, $max);
        return $id;
    }

    // 1. cek ke tabel access_token_bpd data terakhir
    // 2. cek expired token
    // 3. jika expired generate token baru
    // 4. jika belum expired ambil token terakhir
    function handleGetToken()
    {
        $accessTokenRepository = $this->getRepository(AccessTokenBpd::class);
        $bpdSnap = $this->get(BpdSnapService::class);
        $dataAccess = $accessTokenRepository->findOneBy([], ['id' => 'DESC']);
        $dataExpired = new DateTime($dataAccess->getExpiredDate()->format('Y-m-d H:i:s'), new DateTimeZone('Asia/Makassar'));
        $dataNow = Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s');
        $dataNow = new DateTime($dataNow, new DateTimeZone('Asia/Makassar'));
        $hasToken = false;
        $getToken = null;
        if ($dataExpired > $dataNow) {
            $hasToken = true;
            $getToken = $dataAccess;
        }
        $em = $this->getEntityManager();

        if (!$hasToken) {
            $accessToken = $bpdSnap->accessToken();
            $accessToken = json_decode($accessToken);
            if (isset($accessToken->responseMessage) && $accessToken->responseMessage == "Success") {
                $expiredDate = Carbon::now('Asia/Makassar')->addSeconds($accessToken->expiresIn)->format('Y-m-d H:i:s');
                $covertExpired = new DateTime($expiredDate, new DateTimeZone('Asia/Makassar'));
                $getToken = new AccessTokenBpd();
                $getToken->setToken($accessToken->accessToken);
                $getToken->setExpiredDate($covertExpired);
                $getToken->setCreatedAt();

                $em->persist($getToken);
                $em->flush();
            }
        }

        return $getToken;
    }

    public function ccPayment($id)
    {
        $orderRepository = $this->getRepository(Order::class);
        $bpdCcRepository = $this->getRepository(BpdCc::class);

        $reqBpdRepository = $this->getRepository(BpdRequestBinding::class);


        $accessTokenRepository = $this->getRepository(AccessTokenBpd::class);
        $bpdSnap = $this->get(BpdSnapService::class);

        $em = $this->getEntityManager();
        $user = $this->getUser();

        $orders = $orderRepository->findBy([
            'sharedId' => $id,
        ]);

        $bindings = $reqBpdRepository->findBy([
            'user' => $user,
        ]);

        $dataPayment = $bpdCcRepository->findOneBy([
            'orders' => $orders[0]
        ], ['id' => 'DESC']);

        $getToken = $accessTokenRepository->findOneBy([
            'orderSharedId' => $id,
        ], ['id' => 'DESC']);

        $nominal = (float) round($this->getTotalToBePaidForPayment($id, $orders));
        $dataPaymentExpired = false;
        if ($dataPayment != null) {
            // penyesuaian sementara
            if ($dataPayment->getExpiredIn()) {
                $paymentExpired = new DateTime($dataPayment->getExpiredIn()->format('Y-m-d H:i:s'), new DateTimeZone('Asia/Makassar'));
                $dataNow = Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s');
                $dataNow = new DateTime($dataNow, new DateTimeZone('Asia/Makassar'));
                if ($dataPayment->getStatus() == 'PENDING') {
                    $check = $this->ccPaymentCheck($dataPayment->getId());
                    $check = json_decode($check->getContent());
                    
                    if (isset($check->responseMessage) && $check->responseMessage == "Success") {
                        if ($check->additionalInfo->trxStatus == 'SUCCESS') {
                            $statusCheck = 'SUCCESS';
                        } else if ($check->additionalInfo->trxStatus == 'PENDING') {
                            if ($paymentExpired < $dataNow) {
                                $statusCheck = 'EXPIRED';
                                $dataPaymentExpired = true;
                                $this->addFlash(
                                    'warning',
                                    'Pembayaran Kedaluwarsa, silahkan melakukan pembayaran ulang'
                                );
                            } else {
                                $statusCheck = 'PENDING';
                            }
                        } else {
                            $statusCheck = 'EXPIRED';
                            $dataPaymentExpired = true;
                            $this->addFlash(
                                'warning',
                                'Pembayaran Kedaluwarsa, silahkan melakukan pembayaran ulang'
                            );
                        }
                        $dataPayment->setStatus($statusCheck);
                        $em->persist($dataPayment);
                        $em->flush();
                    } else {
                        $this->addFlash(
                            'warning',
                            $check->responseMessage,
                        );
                    }
                }
            }
            // end penyesuaian sementara
        }

        // sementara langsung dibuat sukses
        // $dataPayment->getStatus() == 'SUCCESS'
        $paymentType = '';
        if($dataPayment && $dataPayment->getOrders()){
            if($dataPayment->getOrders()->getPayment()){
                $paymentType = $dataPayment->getOrders()->getPayment()->getType();
            }
        }
        if ($dataPayment != null && $dataPayment->getStatus() == 'SUCCESS') {
            $dataResponse = json_decode($dataPayment->getResponse());
            $trxTime = '-';
            if (@$dataResponse->additionalInfo->trxDateTime) {
                $trxTime = $dataResponse->additionalInfo->trxDateTime;
            }

            // Carbon::parse($trxTime)->addHours(8)->format('d M Y, H:i:s')
            return $this->view('@__main__/public/order/fragments/payment_cc_success.html.twig', [
                'sharedId' => $id,
                'nominal' => $nominal,
                'dataPayment' => $dataPayment,
                'dataResponse' => $dataResponse,
                'responseTrxTime' => $trxTime,
            ]);
        }

        $accToken = null;
        if ($getToken != null) {
            $dataExpired = new DateTime($getToken->getExpiredDate()->format('Y-m-d H:i:s'), new DateTimeZone('Asia/Makassar'));
            $dataNow = Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s');
            $dataNow = new DateTime($dataNow, new DateTimeZone('Asia/Makassar'));
            if ($dataExpired > $dataNow) {
                $accToken = $getToken;
            } else {
                $em->remove($getToken);
                $em->flush();
            }
        } 
        if ($accToken == null) {
            $dataAccess = $bpdSnap->accessToken();
            $dataAccess = json_decode($dataAccess);
            if (isset($dataAccess->responseMessage) && $dataAccess->responseMessage == "Success") {
                $expiredDate = Carbon::now('Asia/Makassar')->addSeconds($dataAccess->expiresIn)->format('Y-m-d H:i:s');
                $covertExpired = new DateTime($expiredDate, new DateTimeZone('Asia/Makassar'));
                $covertExpired2 = new DateTime($expiredDate);
                $accToken = new AccessTokenBpd();
                $accToken->setToken($dataAccess->accessToken);
                $accToken->setExpiredDate($covertExpired);
                $accToken->setOrderSharedId($id);
                $accToken->setCreatedAt();

                $em->persist($accToken);
                $em->flush();
            } else {
                $this->addFlash(
                    'warning',
                    'Terdapat kesalahan ketika request token'
                );
                return $this->redirectToRoute('user_ppktreasurer_detail', ['id' => $orders[0]->getId()]);
            }
        }
        
        // dd($dataPaymentExpired, $dataPayment, $paymentExpired, $dataNow);
        // dd($getBindingId->getUser()->getId());
        // $this->logger->error(sprintf('EXPIRED DATE TOKEN TIMEJON %s', $accToken->getExpiredDate()->getTimezone()->getName()));

        return $this->view('@__main__/public/order/fragments/payment_cc.html.twig', [
            'sharedId' => $id,
            'id' => $orders[0]->getId(),
            'nominal' => $nominal,
            'accToken' => $accToken,
            'dataPayment' => $dataPayment,
            'paymentType' => $paymentType,
            'dataPaymentExpired' => $dataPaymentExpired,
            'expiredTime' => $dataPayment != null ? $dataPayment->getExpiredIn()->format('d M Y, H:i:s') : null,
            'token_id' => 'cc_payment_tokodaring',
            'additional_data' => 'create',
        ]);
    }
    
    function shortUuid()
    {
        // Generate a UUID
        $uuid = Uuid::uuid4()->toString();

        // Remove the hyphens
        $uuid = str_replace('-', '', $uuid);

        // Take the first 12 characters
        return $shortUuid = substr($uuid, 0, 12);
    }
    function ccPaymentStore($id)
    {
        $request = $this->getRequest();
        // dd($request);
        $orderRepository = $this->getRepository(Order::class);
        $bpdCcRepository = $this->getRepository(BpdCc::class);
        $reqBpdRepository = $this->getRepository(BpdRequestBinding::class);
        $accessTokenRepository = $this->getRepository(AccessTokenBpd::class);
        $bpdSnap = $this->get(BpdSnapService::class);
        $em = $this->getEntityManager();
        $user = $this->getUser();

        $getToken = $accessTokenRepository->findOneBy([
            'orderSharedId' => $id,
        ]);

        $orders = $orderRepository->findBy([
            'sharedId' => $id,
        ]);

        $nominal = number_format((float)round($this->getTotalToBePaidForPayment($id, $orders)), 2, '.', '');
        if ($getToken != null) {
            $dataExpired = new DateTime($getToken->getExpiredDate()->format('Y-m-d H:i:s'), new DateTimeZone('Asia/Makassar'));
            $dataNow = Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s');
            $dataNow = new DateTime($dataNow, new DateTimeZone('Asia/Makassar'));
            if ($dataExpired > $dataNow) {

                $cpan = $request->request->get('cpan', null);
                $ott = $request->request->get('ott', null);
                $submit_normal = $request->request->get('submit_normal', null);
                $submit_single = $request->request->get('submit_single', null);

                $approveSend = false;
                // cpan dan ott tidak boleh kosong saat pembayaran manual

                if ($cpan != null && $ott != null || (isset($submit_normal) || isset($submit_single))) {
                    if (strlen($cpan) <= 19 && strlen($ott) <= 8) {
                        $approveSend = true;
                    }
                }


                if (!isset($submit_normal) && !isset($submit_single)) {
                    $bpdReqBind = $reqBpdRepository->findOneBy(['customerPan' => $cpan]);
                    if ($bpdReqBind) {
                        $this->addFlash(
                            'warning',
                            'Pembayaran gagal, CustomerPan sudah pernah dilakukan proses binding.'
                        );
                        return $this->redirectToRoute('user_order_cc_payment', ['id' => $id]);
                    }
                }
                if ($approveSend) {
                    $request_data = [
                        'nominal' => $nominal,
                        'partnerReferenceNo' => str_pad($orders[0]->getId(), 12, "0", STR_PAD_LEFT),
                        'cpan' => $cpan,
                        'ott' => $ott,
                        'externalId' => $this->shortUuid(),
                        'items' => 'Pembayaran Invoice Tokodaring Balimall ' . $orders[0]->getInvoice(),
                        // 'merchantExpired' => Carbon::now('Asia/Makassar')->addSeconds(expiredIn)->format('Y-m-d\TH:i:sP'),
                    ];
                


                    $isBinding = $request->request->get('isBinding', null);
                    $isBindingAuth = null;
                    $manualWithBinding = null;
                    // check apakah radio button save data aktif saat pembayaran manual
                    if (isset($isBinding) && !empty($isBinding)) {
                        if (!isset($submit_normal) && !isset($submit_single)) {
                            $manualWithBinding = 'Y';
                            $isBindingAuth = 'Y';
                            $isBinding = 'N';
                        }
                    }

                    // pembayaran manual + binding
                    if ($manualWithBinding != null) {
                        $requestBinding = new BpdRequestBinding();
                        $requestBinding->setCustomerPan(filter_var($request_data['cpan'], FILTER_SANITIZE_STRING));
                        $requestBinding->setOtt(filter_var($request_data['ott'], FILTER_SANITIZE_STRING));
                        $requestBinding->setPartnerReferenceNo($request_data['partnerReferenceNo']);
                        $requestBinding->setStatus(0);
                        $requestBinding->setUser($user);
                        $dataCpan = $requestBinding->getCustomerPan();
                    } else {
                        if (isset($submit_normal)) {
                            $idBinding = $submit_normal;
                            $dataCpan = $reqBpdRepository->find($idBinding)->getCustomerPan();
                        } elseif ($submit_single) {
                            $idBinding = $submit_single;
                            $dataCpan = $reqBpdRepository->find($idBinding)->getCustomerPan();
                        } else {
                            $dataCpan = $cpan;
                        }
                    }
                    
                
                    // PAYMENT BINDING
                    // check apakah dia menggunakan pembayaran normal atau single flow dari list binding
                    if (isset($submit_normal) && !empty($submit_normal)) {
                        // NORMAL
                        $isBinding = 'Y';
                        $isBindingAuth = 'Y';
                        $binding = $reqBpdRepository->find($submit_normal);
                        $request_data['bindingType'] = 'normal';
                        $request_data['bindingToken'] = $binding->getIssuerToken();
                        $request_data['cpan'] = $binding->getCustomerPan();
                    } else if (isset($submit_single) && !empty($submit_single)) {
                        // SINGLE FLOW
                        $isBinding = 'Y';
                        $isBindingAuth = 'Y';
                        $binding = $reqBpdRepository->find($submit_single);
                        $request_data['bindingType'] = 'single';
                        $request_data['bindingToken'] = $binding->getIssuerToken();
                        $request_data['cpan'] = $binding->getCustomerPan();
                    }
                    
                    $data = $bpdSnap->authenticationCpts($getToken->getToken(), $request_data, $isBindingAuth);
                    $result = json_decode($data);
                    // dd($result, $data);
                    if (isset($result->responseMessage) && $result->responseMessage == "Success") {
                        $bpdCc = new BpdCc();
                        $bpdCc->setOrders($orders[0]);
                        $bpdCc->setExternalId($request_data['externalId']);
                        $bpdCc->setAmount($nominal);

                        // akan di sesuaikan ketika sudah tidak bug dari bpd
                        if (isset($submit_single) && !empty($submit_single)) {
                            $bpdCc->setStatus('PENDING');
                        } else {
                            $bpdCc->setStatus('PENDING');
                        }

                        $bpdCc->setReferenceNo($request_data['partnerReferenceNo']);
                        $bpdCc->setCpan($request_data['cpan']);
                        $bpdCc->setOtt($ott);
                        $bpdCc->setRequestData(json_encode($request_data));
                        $bpdCc->setResponse($data);
                        if (isset($result->additionalInfo) && !empty($result->additionalInfo)) {
                            if (@$result->additionalInfo->expiredIn) {
                                $bpdCc->setExpiredIn(new DateTime(Carbon::now('Asia/Makassar')->addSeconds($result->additionalInfo->expiredIn)->format('Y-m-d H:i:s'), new DateTimeZone('Asia/Makassar')));
                            } else {
                                $bpdCc->setExpiredIn(new \DateTime('now'));
                            }
                            $bpdCc->setApprovalCode($result->additionalInfo->approvalCode);
                            $bpdCc->setApprovalCode($result->additionalInfo->approvalCode);
                        }
                        $bpdCc->setCreatedAt();

                        if (isset($isBinding) and $isBinding == 'Y') {
                            $bpdCc->setBindingType($isBinding);
                            if (isset($request_data['bindingToken'])) {
                                $bpdCc->setBindingToken($request_data['bindingToken']);
                            }
                        }

                        $em->persist($bpdCc);
                        $em->flush();

                        if ($manualWithBinding != null) {
                            $em->persist($requestBinding);
                            $em->flush();
                        }

                        $this->addFlash(
                            'success',
                            'Request pembayaran menggunakan Kartu Kredit Indonesia berhasil.'
                        );
                    } else {
                        if ($result->responseMessage == "Token expired (RC 57)" && $result->responseCode == "404NS57"){
                            // dd($binding->getCustomerPan());
                            $this->addFlash(
                                'warning',
                                'Binding anda terhadap Virtual Card Number ini sudah expired, silahkan melakukan binding ulang.',
                            );

                            $this->addFlash(
                                'additional_info',
                                ['is_expired'=> true,]
                            );

                            $updateBinding = $reqBpdRepository->findOneBy(['customerPan'=> $binding->getCustomerPan(), 'user'=> $binding->getUser() ]);
                            // dd($updateBinding);
                            if ($updateBinding) {
                                $updateBinding->setNotes('Binding anda terhadap Virtual Card Number ini sudah expired, silahkan melakukan binding ulang.');
                                $updateBinding->setIsExpired('1');
                                $em->persist($updateBinding);
                                $em->flush();
                            }

                        } else {
                            $this->addFlash(
                                'warning',
                                $result->responseMessage,
                            );
                        }
                    }
                } else {
                    $this->addFlash(
                        'warning',
                        'Terdapat kesalahan, mohon untuk memberikan data yang valid'
                    );
                }
            }
        }

        return $this->redirectToRoute('user_order_cc_payment', ['id' => $id]);
    }

    function ccPaymentCheck($id)
    {
        $repository     = $this->getRepository(Order::class);
        $bpdCcRepository = $this->getRepository(BpdCc::class);
        $accessTokenRepository = $this->getRepository(AccessTokenBpd::class);
        $bpdSnap = $this->get(BpdSnapService::class);
        $em = $this->getEntityManager();

        $dataAccess = $accessTokenRepository->findOneBy([], ['id' => 'DESC']);
        $dataExpired = new DateTime($dataAccess->getExpiredDate()->format('Y-m-d H:i:s'), new DateTimeZone('Asia/Makassar'));
        $dataNow = Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s');
        $dataNow = new DateTime($dataNow, new DateTimeZone('Asia/Makassar'));
        $hasToken = false;
        $getToken = null;
        if ($dataExpired > $dataNow) {
            $hasToken = true;
            $getToken = $dataAccess;
        }
        if (!$hasToken) {
            $accessToken = $bpdSnap->accessToken();
            $accessToken = json_decode($accessToken);
            if (isset($accessToken->responseMessage) && $accessToken->responseMessage == "Success") {
                $expiredDate = Carbon::now('Asia/Makassar')->addSeconds($accessToken->expiresIn)->format('Y-m-d H:i:s');
                $covertExpired = new DateTime($expiredDate, new DateTimeZone('Asia/Makassar'));
                $getToken = new AccessTokenBpd();
                $getToken->setToken($accessToken->accessToken);
                $getToken->setExpiredDate($covertExpired);
                $getToken->setCreatedAt();

                $em->persist($getToken);
                $em->flush();
            }
        }
        $response = [];
        if ($getToken != null) {
            $bpdCredit = $bpdCcRepository->find($id);
            $request_data = [
                'partnerReferenceNo' => str_pad($bpdCredit->getOrders()->getId(), 12, "0", STR_PAD_LEFT),
                'cpan' => $bpdCredit->getCpan(),
                'externalId' => gmdate("His") . $bpdCredit->getId(),
                'approvalCode' => $bpdCredit->getApprovalCode()
                // 'approvalCode' => $bpdCredit->getApprovalCode()
            ];
            if (!empty($bpdCredit->getBindingType())) {
                $request_data['binding'] = $bpdCredit->getBindingType();
                $request_data['bindingToken'] = $bpdCredit->getBindingToken();
            } else {
                $request_data['ott'] = $bpdCredit->getOtt();
            }
            $checkStatus = $bpdSnap->checkStatusCpts($getToken->getToken(), $request_data);
            $response = json_decode($checkStatus);
            if (isset($response->responseMessage) && $response->responseMessage == "Success") {
                if ($response->additionalInfo->trxStatus != "PENDING") {
                    $bpdCredit->setStatus($response->additionalInfo->trxStatus);
                    $bpdCredit->setTrxId($response->additionalInfo->trxId);
                    $bpdCredit->setResponse($checkStatus);
                    $bpdCredit->setUpdatedAt();
                    $em->persist($bpdCredit);
                    $em->flush();

                    if ($response->additionalInfo->trxStatus == "SUCCESS") {
                        $order = $bpdCredit->getOrders();
                        $order->setStatus('paid');

                        $payment = new OrderPayment();
                        $payment->setOrder($order);
                        $payment->setInvoice($order->getInvoice());
                        $payment->setName($order->getName());
                        $payment->setEmail($order->getEmail());
                        $payment->setType('KKI');
                        $payment->setDate(new DateTime('now'));
                        $payment->setAttachment($response->additionalInfo->trxId);
                        $payment->setNominal((int)$bpdCredit->getAmount());
                        $payment->setMessage('Pembayaran menggunakan KKI');
                        $payment->setBankName('bpd_bali');

                        $em->persist($payment);
                        $em->flush();
                        $em->persist($order);
                        $em->flush();
                    }
                }
            }
        }
        $jsonResponse = new Response(json_encode($response));
        $jsonResponse->headers->set('Content-Type', 'application/json');
        return $jsonResponse;
    }

    public function ccPaymentUpdate($id)
    {
        $orderRepository = $this->getRepository(Order::class);
        $bpdCcRepository = $this->getRepository(BpdCc::class);

        $reqBpdRepository = $this->getRepository(BpdRequestBinding::class);


        $accessTokenRepository = $this->getRepository(AccessTokenBpd::class);
        $bpdSnap = $this->get(BpdSnapService::class);

        $em = $this->getEntityManager();
        $user = $this->getUser();

        $orders = $orderRepository->findBy([
            'sharedId' => $id,
        ]);

        $bindings = $reqBpdRepository->findBy([
            'user' => $user,
        ]);

        $dataPayment = $bpdCcRepository->findOneBy([
            'orders' => $orders[0]
        ], ['id' => 'DESC']);

        $getToken = $accessTokenRepository->findOneBy([
            'orderSharedId' => $id,
        ], ['id' => 'DESC']);

        $nominal = (float) round($this->getTotalToBePaidForPayment($id, $orders));
        $dataPaymentExpired = false;
        if ($dataPayment != null) {
            // penyesuaian sementara
            if ($dataPayment->getExpiredIn()) {
                $paymentExpired = new DateTime($dataPayment->getExpiredIn()->format('Y-m-d H:i:s'), new DateTimeZone('Asia/Makassar'));
                $dataNow = Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s');
                $dataNow = new DateTime($dataNow, new DateTimeZone('Asia/Makassar'));
                if ($dataPayment->getStatus() == 'PENDING') {
                    $check = $this->ccPaymentCheck($dataPayment->getId());
                    $check = json_decode($check->getContent());

                    if (isset($check->responseMessage) && $check->responseMessage == "Success") {
                        if ($check->additionalInfo->trxStatus == 'SUCCESS') {
                            $statusCheck = 'SUCCESS';
                        } else if ($check->additionalInfo->trxStatus == 'PENDING') {
                            if ($paymentExpired < $dataNow) {
                                $statusCheck = 'EXPIRED';
                                $dataPaymentExpired = true;
                                $this->addFlash(
                                    'warning',
                                    'Pembayaran Kedaluwarsa, silahkan melakukan pembayaran ulang'
                                );
                            } else {
                                $statusCheck = 'PENDING';
                            }
                        } else {
                            $statusCheck = 'EXPIRED';
                            $dataPaymentExpired = true;
                            $this->addFlash(
                                'warning',
                                'Pembayaran Kedaluwarsa, silahkan melakukan pembayaran ulang'
                            );
                        }
                        $dataPayment->setStatus($statusCheck);
                        $em->persist($dataPayment);
                        $em->flush();
                    } else {
                        $this->addFlash(
                            'warning',
                            $check->responseMessage,
                        );
                    }
                }
            }
            // end penyesuaian sementara
        }

        // sementara langsung dibuat sukses
        // $dataPayment->getStatus() == 'SUCCESS'
        if ($dataPayment != null && $dataPayment->getStatus() == 'SUCCESS') {
            $dataResponse = json_decode($dataPayment->getResponse());
            $trxTime = '-';
            if (@$dataResponse->additionalInfo->trxDateTime) {
                $trxTime = $dataResponse->additionalInfo->trxDateTime;
            }
            // Carbon::parse($trxTime)->addHours(8)->format('d M Y, H:i:s')
            return $this->view('@__main__/public/order/fragments/payment_cc_update.html.twig', [
                'sharedId' => $id,
                'nominal' => $nominal,
                'dataPayment' => $dataPayment,
                'dataResponse' => $dataResponse,
                'responseTrxTime' => $trxTime,
            ]);
        }

        $accToken = null;
        if ($getToken != null) {
            $dataExpired = new DateTime($getToken->getExpiredDate()->format('Y-m-d H:i:s'), new DateTimeZone('Asia/Makassar'));
            $dataNow = Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s');
            $dataNow = new DateTime($dataNow, new DateTimeZone('Asia/Makassar'));
            if ($dataExpired > $dataNow) {
                $accToken = $getToken;
            } else {
                $em->remove($getToken);
                $em->flush();
            }
        }

        if ($accToken == null) {
            $dataAccess = $bpdSnap->accessToken();
            $dataAccess = json_decode($dataAccess);
            if (isset($dataAccess->responseMessage) && $dataAccess->responseMessage == "Success") {
                $expiredDate = Carbon::now('Asia/Makassar')->addSeconds($dataAccess->expiresIn)->format('Y-m-d H:i:s');
                $covertExpired = new DateTime($expiredDate, new DateTimeZone('Asia/Makassar'));
                $accToken = new AccessTokenBpd();
                $accToken->setToken($dataAccess->accessToken);
                $accToken->setExpiredDate($covertExpired);
                $accToken->setOrderSharedId($id);
                $accToken->setCreatedAt();

                $em->persist($accToken);
                $em->flush();
            } else {
                $this->addFlash(
                    'warning',
                    'Terdapat kesalahan ketika request token'
                );
                return $this->redirectToRoute('user_ppktreasurer_detail', ['id' => $orders[0]->getId()]);
            }
        }

        // dd($dataPaymentExpired, $dataPayment, $paymentExpired, $dataNow);

        return $this->view('@__main__/public/order/fragments/payment_cc_update.html.twig', [
            'sharedId' => $id,
            'id' => $orders[0]->getId(),
            'nominal' => $nominal,
            'accToken' => $accToken,
            'dataPayment' => $dataPayment,
            'dataPaymentExpired' => $dataPaymentExpired,
            'expiredTime' => $dataPayment != null ? $dataPayment->getExpiredIn()->format('d M Y, H:i:s') : null,
            'token_id' => 'cc_payment_tokodaring',
        ]);
    }

    public function addendumAggrement($id) {
        $order = $this->getRepository(Order::class)->find($id);
        // dd($order);
        return $this->view('@__main__/public/user/order/addendum/persetujuan_addendum.html.twig', [
            'order' => $order,
        ]);
    }

    public function addendumAgreed($id) {
        $order = $this->getRepository(Order::class)->find($id);
        return $this->view('@__main__/public/user/order/addendum/surat_addendum.html.twig', [
            'order' => $order,
        ]);
    }
}
