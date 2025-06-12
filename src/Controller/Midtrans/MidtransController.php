<?php

namespace App\Controller\Midtrans;

use App\Controller\PublicController;
use App\Entity\Midtrans;
use App\Entity\Order;
use App\Entity\OrderPayment;
use App\Service\MidtransService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use DateTime;

class MidtransController extends PublicController
{
    private $logger;

    public function __construct(TranslatorInterface $translator, ValidatorInterface $validator, LoggerInterface $logger)
    {
        parent::__construct($translator, $validator);

        $this->logger = $logger;
    }

    public function notification(): JsonResponse
    {
        $notificationBody = json_decode(file_get_contents('php://input'), true);

        $this->logger->error('Midtrans webhook request ', [json_encode($notificationBody)]);

        $midtransService = $this->get(MidtransService::class);

        try {
            if (false === $midtransService->verifyNotification($notificationBody)){
                return new JsonResponse(null, 401);
            }
        }catch (\Throwable $throwable) {
            return new JsonResponse(null, 401);
        }

        $transaction = $notificationBody['transaction_status'];
        $type = $notificationBody['payment_type'];
        $fraud = $notificationBody['fraud_status'] ?? '';

        if ($transaction == 'capture') {
            // For credit card transaction, we need to check whether transaction is challenge by FDS or not
            if ($type == 'credit_card'){
                if($fraud == 'challenge'){
                    $this->saveResponse($fraud, $notificationBody);
                } else {
                    $this->saveResponse('success', $notificationBody);
                }
            }
        }
        else if ($transaction == 'settlement'){
            $this->saveResponse('success', $notificationBody);
        }
        else if($transaction == 'pending'){
            $this->saveResponse($transaction, $notificationBody);
        }
        else if ($transaction == 'deny') {
            $this->saveResponse($transaction, $notificationBody);
        }
        else if ($transaction == 'expire') {
            $this->saveResponse($transaction, $notificationBody);
        }
        else if ($transaction == 'cancel') {
            $this->saveResponse($transaction, $notificationBody);
        }

        return new JsonResponse(null, 200);
    }

    public function saveResponse($type, $notificationBody):void
    {
        $orderId = $notificationBody['order_id'];

        $midtransRepository = $this->getRepository(Midtrans::class);
        $midtrans = $midtransRepository->findOneBy(['orderId' => $orderId]);

        $em = $this->getEntityManager();

        if ($midtrans) {
            $status = $type === 'challenge' ? $type : $notificationBody['transaction_status'];
            $midtrans->setStatus($status);
            $midtrans->setTransactionTime($notificationBody['transaction_time']);
            $midtrans->setTransactionId($notificationBody['transaction_id']);
            $midtrans->setPaymentType($notificationBody['payment_type']);
            $midtrans->setResponse($notificationBody);

            $em->persist($midtrans);
            $em->flush();

            $orderRepository = $this->getRepository(Order::class);
            $orders = $orderRepository->findBy(['sharedInvoice' => $midtrans->getSharedInvoice()]);

            if (count($orders) > 0) {
                foreach ($orders as $order) {

                    $previousOrderValues = clone $order;

                    if (str_contains('FDS', $order->getNote())){
                        $order->setNote('');
                    }

                    if ($type === 'success') {
                        $order->setStatus('paid');
                        $order->setStatusChangeTime();

                        $this->setDisbursementProductFee($em, $order);

                        $orderPayment = new OrderPayment();
                        $orderPayment->setOrder($order);
                        $orderPayment->setInvoice($order->getInvoice());
                        $orderPayment->setName($order->getName());
                        $orderPayment->setEmail($order->getEmail());
                        $orderPayment->setType('midtrans');
                        $orderPayment->setNominal($notificationBody['gross_amount']);
                        $orderPayment->setBankName($notificationBody['va_numbers'][0]['bank'] ?? null);
                        $orderPayment->setAttachment($midtrans->getToken());
                        $orderPayment->setMessage(sprintf('Pembayaran menggunakan Midtrans %s %s', $notificationBody['payment_type'], $notificationBody['store'] ?? ''));

                        try {
                            $orderPayment->setDate(new DateTime('now'));
                        } catch (\Exception $e) {
                        }

                        $em->persist($orderPayment);
                    }elseif ($type === 'challenge') {
                        $order->setNote('Challenge by FDS, admin should decide whether this transaction is authorized or not in MAP');
                    }elseif ($type === 'deny'){
                        continue;
                    } elseif ($type === 'expire' || $type === 'cancel') {
                        $order->setMidtransId(null);
                    }

                    $em->persist($order);
                    $em->flush();

                    $this->logOrder($em, $previousOrderValues, $order, $order->getBuyer());
                }
            }
        }
    }

}
