<?php

namespace App\Command;

use App\Entity\Midtrans;
use App\Entity\Order;
use App\Entity\OrderPayment;
use App\Traits\AppTrait;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Midtrans\Config;
use Midtrans\Transaction;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DateTime;

class CheckMidtransStatusCommand extends Command
{
    use AppTrait;

    /** @var ObjectManager $manager */
    private $manager;
    private $logger;

    public function __construct(Registry $registry, LoggerInterface $logger)
    {
        parent::__construct();
        Config::$serverKey = getenv('MIDTRANS_SERVER_KEY');
        Config::$isProduction = getenv('APP_URL') === 'https://tokodaring.balimall.id';
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $this->manager = $registry->getManager();
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:check-midtrans-status')
            ->setDescription('Automatic check for Midtrans payment status.')
            ->setHelp('This command allows you to automatically check for the status of Midtrans payment status.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
       $midtransRepository = $this->manager->getRepository(Midtrans::class);

       $orderIds = [];
       $midtransData = $midtransRepository->getPendingPayment();

        if (count($midtransData) > 0) {
           foreach ($midtransData as $item) {
               try {
                   $response = Transaction::status($item['orderId']);

                   if ($response) {
                       $response = json_decode((string) $response, true);

                       $trx_id = $response['transaction_id'];
                       $transaction = $response['transaction_status'];
                       $type = $response['payment_type'];
                       $fraud = $response['fraud_status'] ?? '';

                       if ($transaction !== $item['status'] && $trx_id === $item['transactionId']) {
                           if ($transaction == 'capture') {
                               // For credit card transaction, we need to check whether transaction is challenge by FDS or not
                               if ($type == 'credit_card'){
                                   if($fraud == 'challenge'){
                                       $this->saveResponse($fraud, $response);
                                   } else {
                                       $this->saveResponse('success', $response);
                                   }
                               }
                           }
                           else if ($transaction == 'settlement'){
                               $this->saveResponse('success', $response);
                           }
                           else if($transaction == 'pending'){
                               $this->saveResponse($transaction, $response);
                           }
                           else if ($transaction == 'deny') {
                               $this->saveResponse($transaction, $response);
                           }
                           else if ($transaction == 'expire') {
                               $this->saveResponse($transaction, $response);
                           }
                           else if ($transaction == 'cancel') {
                               $this->saveResponse($transaction, $response);
                           }
                       }
                   }

               }catch (\Throwable $throwable) {
                   $this->logger->error('Error', [$throwable->getMessage()]);
               }
           }
       }

        $this->logger->error('Midtrans pending payment ', [$orderIds]);

        return 1;
    }

    protected function saveResponse($type, $notificationBody):void
    {
        $orderId = $notificationBody['order_id'];

        $midtransRepository = $this->manager->getRepository(Midtrans::class);
        $midtrans = $midtransRepository->findOneBy(['orderId' => $orderId]);

        if ($midtrans) {
            $status = $type === 'challenge' ? $type : $notificationBody['transaction_status'];
            $midtrans->setStatus($status);
            $midtrans->setTransactionTime($notificationBody['transaction_time']);
            $midtrans->setTransactionId($notificationBody['transaction_id']);
            $midtrans->setPaymentType($notificationBody['payment_type']);
            $midtrans->setResponse($notificationBody);

            $this->manager->persist($midtrans);
            $this->manager->flush();

            $orderRepository = $this->manager->getRepository(Order::class);
            $orders = $orderRepository->findBy(['sharedInvoice' => $midtrans->getSharedInvoice()]);

            if (count($orders) > 0) {
                foreach ($orders as $order) {

                    $previousOrderValues = clone $order;

                    if (str_contains('FDS', $order->getNote())){
                        $order->setNote('');
                    }

                    if ($type === 'success') {
                        $order->setStatus($order->getIsB2gTransaction() ? 'payment_process' : 'paid');

                        $orderPayment = new OrderPayment();
                        $orderPayment->setOrder($order);
                        $orderPayment->setInvoice($order->getInvoice());
                        $orderPayment->setName($order->getName());
                        $orderPayment->setEmail($order->getEmail());
                        $orderPayment->setType('midtrans');
                        $orderPayment->setNominal($notificationBody['gross_amount']);
                        $orderPayment->setBankName($notificationBody['bank'] ?? null);
                        $orderPayment->setAttachment($midtrans->getToken());
                        $orderPayment->setMessage(sprintf('Pembayaran menggunakan Midtrans %s', $notificationBody['payment_type']));

                        try {
                            $orderPayment->setDate(new DateTime('now'));
                        } catch (\Exception $e) {
                        }

                        $this->manager->persist($orderPayment);
                    }elseif ($type === 'challenge') {
                        $order->setNote('Challenge by FDS, admin should decide whether this transaction is authorized or not in MAP');
                    }elseif ($type === 'deny'){
                        continue;
                    } elseif ($type === 'expire' || $type === 'cancel') {
                        $order->setMidtransId(null);
                    }

                    $this->manager->persist($order);
                    $this->manager->flush();

                    $this->logger->error('Midtrans transaction updated ', [$order->getId()]);

                    $this->logOrder($this->manager, $previousOrderValues, $order, $order->getBuyer());
                }
            }
        }
    }


}
