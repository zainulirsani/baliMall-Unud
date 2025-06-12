<?php

namespace App\Command;

use App\Entity\Doku;
use App\Entity\Notification;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use DateTime;

class CancelDokuFailedOrderCommand extends Command
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
            ->setName('app:cancel-doku-status-failed')
            ->setDescription('Automatic cancel for DOKU failed payment status.')
            ->setHelp('This command allows you to automatically cancel for the status of DOKU failed or expired payment.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dokuRepository = $this->manager->getRepository(Doku::class);
        $orderRepository = $this->manager->getRepository(Order::class);

        $dokuData = $dokuRepository->getFailedPayment();

        $orderList = [];

        if (count($dokuData) > 0) {
            foreach ($dokuData as $item) {
                $isDateExpired = new DateTime('now') > $item->getExpiredDate();

                if ($item->getStatus() === 'PENDING' && $isDateExpired) {
                    $invoiceNumber = $item->getInvoiceNumber();

                    $orders = $orderRepository->findBy(['dokuInvoiceNumber' => $invoiceNumber]);

                    if (count($orders)) {
                        if ($orders[0]->getStatus() === 'pending') {
                            $item->setStatus('EXPIRED');
                            $this->manager->persist($item);
                            $this->manager->flush();
                        }
                    }
                }

                if ($item->getStatus() === 'FAILED' || $item->getStatus() === 'EXPIRED') {
                    $invoiceNumber = $item->getInvoiceNumber();

                    $orders = $orderRepository->findBy(['dokuInvoiceNumber' => $invoiceNumber]);

                    if (count($orders) > 0) {
                        if ($orders[0]->getStatus() === 'pending') {
                            foreach ($orders as $order) {

                                $orderList[] = $order->getId();

                                $order->setDokuInvoiceNumber(null);
                                $this->manager->persist($order);
                                $this->manager->flush();

                                $notification = new Notification();
                                $notification->setSellerId($order->getSeller()->getId());
                                $notification->setBuyerId($order->getBuyer()->getId());
                                $notification->setIsSentToSeller(false);
                                $notification->setIsSentToBuyer(false);
                                $notification->setTitle('Pembayaran doku');
                                $notification->setContent('Pembayaran dengan doku anda gagal, mohon untuk melakukan pembayaran ulang');

                                $this->manager->persist($notification);
                                $this->manager->flush();
                            }
                        }
                    }
                }
            }
        }

        $this->logger->error('Automatic doku cancel ! Order ids ', $orderList);

        return 1;
    }
}
