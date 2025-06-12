<?php

namespace App\Controller\Doku;

use App\Controller\PublicController;
use App\Entity\User;
use App\Entity\Doku;
use App\Entity\Order;
use App\Entity\OrderPayment;
use App\EventListener\OrderChangeListener;
use App\Service\DJPService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use DateTime;

class DokuController extends PublicController
{
    private $logger;

    public function __construct(TranslatorInterface $translator, ValidatorInterface $validator, LoggerInterface $logger)
    {
        parent::__construct($translator, $validator);

        $this->logger = $logger;
    }

    public function qrisNotifications() : JsonResponse
    {
        try {
            $issuerId = $_POST['ISSUERID'];
            $txnDate = $_POST['TXNDATE'];
            $invoice = $_POST['INVOICE'];
            $merchantPan = $_POST['MERCHANTPAN'];

            $sharedKey = getenv('JOKUL_QRIS_SHARED_KEY');
        }catch (\Exception $exception) {
            return new JsonResponse(null, 400);
        }

        /**
         * Security parameter from DOKU. Hashed SHA1 from element: (ISSUERID + TXNDATE + MERCHANTPAN + INVOICE + sharedKey)
         */
        $finalSignature = sha1(sprintf('%s%s%s%s%s', $issuerId, $txnDate, $merchantPan, $invoice, $sharedKey));

        $this->logger->error(sprintf('DOKU-QRIS-NOTIFICATION-PAYLOAD: %s', file_get_contents('php://input')));

        if (!isset($_POST['WORDS'])) {
            return new JsonResponse(null, 400);
        }

        if ($finalSignature !== $_POST['WORDS']) {

            $this->logger->error('DOKU-QRIS-VALIDATE-SIGNATURE-FAILED '
                .json_encode(['final_signature' => $finalSignature, 'request_signature' => $_POST['WORDS']]));

            return new JsonResponse('Invalid Signature', 400);
        }

        try {
            $dokuRepository = $this->getRepository(Doku::class);

            $dokuDetail = $dokuRepository->findOneBy(['invoice_number' => $_POST['TRANSACTIONID']]);

            if ($dokuDetail) {
                $status = $_POST['TXNSTATUS'] === 'S' ? 'SUCCESS' : 'FAILED';

                $response = [
                    'CUSTOMERPAN' => $_POST['CUSTOMERPAN'],
                    'TXNDATE' => $_POST['TXNDATE'],
                    'TERMINALID' => $_POST['TERMINALID'],
                    'ISSUERID' => $_POST['ISSUERID'],
                    'ISSUERNAME' => $_POST['ISSUERNAME'],
                    'WORDS' => $_POST['WORDS'],
                    'CUSTOMERNAME' => $_POST['CUSTOMERNAME'],
                    'ORIGIN' => $_POST['ORIGIN'],
                    'CONVENIENCEFEE' => $_POST['CONVENIENCEFEE'],
                    'ACQUIRER' => $_POST['ACQUIRER'],
                    'MERCHANTPAN' => $_POST['MERCHANTPAN'],
                    'INVOICE' => $_POST['INVOICE'],
                    'REFERENCEID' => $_POST['REFERENCEID']
                ];

                $dokuDetail->setAcquirer($_POST['ACQUIRER']);
                $dokuDetail->setChannel('QRIS');
                $dokuDetail->setService('QRIS');
                $dokuDetail->setResponse($response);
                $dokuDetail->setStatus($status);

                $em = $this->getEntityManager();
                $em->persist($dokuDetail);
                $em->flush();

                if ($status === 'SUCCESS') {
                    $orderRepository = $this->getRepository(Order::class);

                    $orders = $orderRepository->findBy(['dokuInvoiceNumber' => $dokuDetail->getInvoiceNumber()]);

                    if (count($orders) > 0) {
                        foreach ($orders as $order) {

                            $previousOrderValues = clone $order;

                            $order->setStatus('paid');
                            $order->setStatusChangeTime();

                            if ((empty($order->getDjpReportStatus()) || $order->getDjpReportStatus() !== 'djp_report_sent') &&
                                $order->getTaxType() == 58 && 
                                ($order->getBuyer()->getIsUserTesting() != true && $order->getSeller()->getUser()->getIsUserTesting() != true)) 
                            {
                                // $djpService = $this->get(DJPService::class);
                                // $result_barang = $djpService->postTransations($order);

                                // $djpStatus = 'djp_report_failed';

                                // if ($result_barang['error'] === false) {
                                //     $djpStatus = 'djp_report_sent';
                                //     $order->setDjpResponseOrder(json_encode($result_barang));
                                // }


                                // $order->setDjpReportStatus($djpStatus);
                            }

                            $orderPayment = new OrderPayment();
                            $orderPayment->setOrder($order);
                            $orderPayment->setInvoice($order->getInvoice());
                            $orderPayment->setName($order->getName());
                            $orderPayment->setEmail($order->getEmail());
                            $orderPayment->setType('doku');
                            $orderPayment->setNominal($dokuDetail->getAmount());
                            $orderPayment->setMessage(sprintf('Pembayaran menggunakan Doku %s', $dokuDetail->getService()));
                            $orderPayment->setBankName($dokuDetail->getAcquirer());
                            $orderPayment->setAttachment($dokuDetail->getUrl());

                            try {
                                $orderPayment->setDate(new DateTime('now'));
                            } catch (\Exception $e) {
                            }

                            $em->persist($orderPayment);
                            $em->persist($order);
                            $em->flush();

                            $this->logOrder($em, $previousOrderValues, $order, $order->getBuyer());
                        }

                        $this->logger->error(sprintf('DOKU-QRIS-UPDATE-TRANSACTION-SUCCESS: %s', $orders[0]->getDokuInvoiceNumber()));
                    }
                }
            }

            return new JsonResponse(null, 200);

        }catch (\Throwable $exception) {
            $this->logger->error(sprintf('DOKU-QRIS-UPDATE-TRANSACTION-ERROR: %s', $exception->getMessage()));

            return new JsonResponse(null, 500);
        }
    }

    public function vaNotifications() : JsonResponse
    {
        $notificationPath = getenv('DOKU_VA_NOTIFICATION_URL');

        // if ($this->validateSignature($notificationPath, 'VA') === false) {
        //     return new JsonResponse(null, 400);
        // }

        $this->logger->error(sprintf('DOKU-VA-UPDATE-PAYLOAD: %s', file_get_contents('php://input')));

        $notificationBody = json_decode(file_get_contents('php://input'), true);

        try {
            $this->saveResponse($notificationBody);

            return new JsonResponse(null, 200);
        }catch (\Throwable $exception) {
            $this->logger->error(sprintf('DOKU-VA-UPDATE-TRANSACTION-ERROR: %s', $exception->getMessage()));

            return new JsonResponse(null, $exception->getCode());
        }
    }

    public function ccNotifications(): JsonResponse
    {
        $notificationPath = getenv('DOKU_CC_NOTIFICATION_URL');

        // if ($this->validateSignature($notificationPath, 'CC') === false) {
        //     return new JsonResponse(null, 400);
        // }

        $notificationBody = json_decode(file_get_contents('php://input'), true);

        try {

            $this->saveResponse($notificationBody);

            return new JsonResponse(null, 200);

        }catch (\Throwable $exception) {
            $this->logger->error(sprintf('DOKU-CC-UPDATE-TRANSACTION-ERROR: %s', $exception->getMessage()));

            return new JsonResponse(null, 500);
        }
    }

    private function saveResponse($notificationBody): void
    {
        $dokuRepository = $this->getRepository(Doku::class);

        $dokuDetail = $dokuRepository->findOneBy(['invoice_number' => $notificationBody['order']['invoice_number']]);

        if ($dokuDetail) {
            $status = $notificationBody['transaction']['status'];

            $dokuDetail->setAcquirer($notificationBody['acquirer']['id']);
            $dokuDetail->setService($notificationBody['service']['id']);
            $dokuDetail->setChannel($notificationBody['channel']['id']);
            $dokuDetail->setResponse($notificationBody);
            $dokuDetail->setStatus($status);

            $em = $this->getEntityManager();

            $em->persist($dokuDetail);
            $em->flush();

            if (strtoupper($status) === 'SUCCESS') {
                $orderRepository = $this->getRepository(Order::class);
                $orders = $orderRepository->findBy(['dokuInvoiceNumber' => $dokuDetail->getInvoiceNumber()]);

                if (count($orders) > 0) {
                    foreach ($orders as $order) {
                        $previousOrderValues = clone $order;

                        $order->setStatus('paid');
                        $order->setUpdatedAt();
                        if ((empty($order->getDjpReportStatus()) || $order->getDjpReportStatus() !== 'djp_report_sent') &&
                            $order->getTaxType() == 58 && 
                            ($order->getBuyer()->getIsUserTesting() != true && $order->getSeller()->getUser()->getIsUserTesting() != true)) 
                        {
                            // $djpService = $this->get(DJPService::class);
                            // $result_barang = $djpService->postTransations($order);

                            // $djpStatus = 'djp_report_failed';

                            // if ($result_barang['error'] === false) {
                            //     $djpStatus = 'djp_report_sent';
                            //     $order->setDjpResponseOrder(json_encode($result_barang));
                            // }


                            // $order->setDjpReportStatus($djpStatus);
                        }

                        // $this->setDisbursementProductFee($em, $order);

                        $orderPayment = new OrderPayment();
                        $orderPayment->setOrder($order);
                        $orderPayment->setInvoice($order->getInvoice());
                        $orderPayment->setName($order->getName());
                        $orderPayment->setEmail($order->getEmail());
                        $orderPayment->setType('doku');
                        $orderPayment->setNominal($dokuDetail->getAmount());
                        $orderPayment->setMessage(sprintf('Pembayaran menggunakan Doku %s', $dokuDetail->getService()));
                        $orderPayment->setBankName($dokuDetail->getAcquirer());
                        $orderPayment->setAttachment($dokuDetail->getUrl());

                        $treasurer = $this->getRepository(User::class)->find($order->getTreasurerId());
                        $this->logOrder($em, $previousOrderValues, $order, $treasurer);

                        try {
                            $orderPayment->setDate(new DateTime('now'));
                        } catch (\Exception $e) {
                        }

                        $em->persist($order);
                        $em->persist($orderPayment);
                        $em->flush();
                    }

                    $this->logger->info(sprintf('DOKU-UPDATE-TRANSACTION-SUCCESS: %s', $orders[0]->getDokuInvoiceNumber()));
                }
            }
        }
    }

    private function validateSignature($notificationPath, $channel): bool
    {
        $notificationHeader = getallheaders();

        $notificationBody = file_get_contents('php://input');

        $this->logger->error(sprintf('DOKU-%s-NOTIFICATION-HEADERS: %s', $channel, json_encode($notificationHeader)));
        $this->logger->error(sprintf('DOKU-%s-NOTIFICATION-PAYLOAD: %s', $channel, $notificationBody));

        try {
            $finalSignature = $this->generateSignature($notificationHeader, $notificationBody, $notificationPath);
        }catch (\Throwable $exception) {
            return false;
        }

        $headers = [];

        foreach ($notificationHeader as $key => $value) {
            $headers[strtolower($key)] = $value;
        }

        if ($finalSignature !== $headers['signature']) {

            $this->logger->error(sprintf('DOKU-%s-FAILED-SIGNATURE: final signature => %s , request signature => %s', $channel, $finalSignature, $notificationHeader['Signature']));

            return false;
        }

        return true;
    }

    private function generateSignature($notificationHeader, $notificationBody, $notificationPath): string
    {
        $secretKey = getenv('JOKUL_SECRET_KEY');

        $digest = base64_encode(hash('sha256', $notificationBody, true));

        $headers = [];

        foreach ($notificationHeader as $key => $value) {
            $headers[strtolower($key)] = $value;
        }

        $clientId = $headers['client-id'];
        $requestId = $headers['request-id'];
        $requestTimestamp = $headers['request-timestamp'];

        $rawSignature = "Client-Id:" . $clientId . "\n"
            . "Request-Id:" . $requestId . "\n"
            . "Request-Timestamp:" . $requestTimestamp . "\n"
            . "Request-Target:" . $notificationPath . "\n"
            . "Digest:" . $digest;

        $signature = base64_encode(hash_hmac('sha256', $rawSignature, $secretKey, true));

        return 'HMACSHA256=' . $signature;
    }
}
