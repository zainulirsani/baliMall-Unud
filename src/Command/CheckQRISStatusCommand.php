<?php

namespace App\Command;

use App\Entity\Qris;
use App\Entity\Order;
use App\Entity\OrderPayment;
use App\Entity\Notification;
use App\Repository\QrisRepository;
use App\Service\QRISClient;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckQRISStatusCommand extends Command
{
    /** @var ObjectManager $manager */
    private $manager;

    private $logger;

    public function __construct(Registry $registry, LoggerInterface $logger)
    {
        parent::__construct();

        $this->manager = $registry->getManager();
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:check-qris-status')
            ->setDescription('Automatic checker for QRIS payment status.')
            ->setHelp('This command allows you to automatically check for the status of QRIS payment.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QrisRepository $repository */
        $repository = $this->manager->getRepository(Qris::class);
        /** @var Qris[] $payments */
        $payments = $repository->findBy([
            'qrStatus' => 'Belum Terbayar',
        ]);

        if (count($payments) > 0) {
            $qrisClient = new QRISClient();

            foreach ($payments as $qris) {
                try {
                    
                    $client = clone $qrisClient;
                    $client->setRequestParameters(['qrValue' => $qris->getQrValue()], 'status_check');

                    $parameters = $client->getRequestParameters();
                    $hashcodeKey = $parameters['hashcodeKey'] ?? '';
                    $response = $client->execute();

                    // if (!$response['error']) {
                    //     $data = $response['data'];

                    //     if (isset($data['errorCode']) && in_array($data['errorCode'], ['IB-1009', 'IB-0500'])) {
                    //         $this->logger->error('QRIS check status error!', $response);
                    //     } else {
                    //         $payment->setRecordId($data['id']);
                    //         $payment->setTrxId($data['trxId']);
                    //         $payment->setReferenceNumber($data['referenceNumber']);
                    //         $payment->setQrStatus($data['status']);
                    //         $payment->setMid($data['mid']);
                    //         $payment->setCreatedDate(date('Y-m-d H:i:s', strtotime($data['created'])));
                    //         $payment->setExpiredDate(date('Y-m-d H:i:s', strtotime($data['expired'])));

                    //         $this->manager->persist($payment);
                    //     }
                    // } 
                    if (!$response['error']) {
                        // $this->logger->error('QRIS Check Payment Status Response', [$sendParam,$response]);
    
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
    
                            $this->manager->persist($qris);
                            $this->manager->flush();
    
                            if ($status === 'Sudah Terbayar') {
                                $this->logger->error('QRIS Check Payment Status Response', $response['data']);
                                $dateParts = explode(' ', $trxDate);
                                $paymentDate = explode('/', $dateParts[0]);
    
                                /** @var OrderRepository $orderRepository */
                                $orderRepository = $this->manager->getRepository(Order::class);
                                /** @var Order[] $orders */
                                $orders = $orderRepository->findBy(['qrisBillNumber' => $billNumber]);
                                $parameters['type'] = 'qris';
                                $parameters['attachment'] = $hashcodeKey;
                                $parameters['message'] = 'Pembayaran menggunakan QRIS';
                                $parameters['date'] = sprintf('%s-%s-%s', $paymentDate[2], $paymentDate[1], $paymentDate[0]);
    
                                // ==========
                                $paymentRepository = $this->manager->getRepository(OrderPayment::class);
                                

                                foreach ($orders as $order) {
                                    $order->setStatus('paid');
                                    $order->setStatusChangeTime();

                                    // $this->setDisbursementProductFee($em, $order);

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

                                    $this->manager->persist($order);
                                    $this->manager->persist($payment);
                                    $this->manager->persist($notification);
                                    $this->manager->flush();
                                }
                            }
                        }
                    }
                    else {
                        $this->logger->error('QRIS check status error!', $response);
                    }
                } catch (Exception $e) {
                    $this->logger->error(sprintf('QRIS exception handler from %s!', __METHOD__), ['message' => $e->getMessage()]);
                }
            }

            $this->manager->flush();
        }

        return 1;
    }
}
