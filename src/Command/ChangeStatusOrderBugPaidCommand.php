<?php

namespace App\Command;
use App\Entity\Order;
use App\Entity\OrderChangeLog;
use App\Repository\OrderRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ChangeStatusOrderBugPaidCommand extends Command
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
            ->setName('app:change-status-order-bug-paid')
            ->setDescription('Change status bug when rating')
            ->setHelp('This command allows you to change status order')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data_bug = $this->manager->getRepository(OrderChangeLog::class)->getOrderBugStatusRating();
        foreach ($data_bug as $key => $value) {
            if ($value['order_status'] != 'paid') {
                $order = $this->manager->getRepository(Order::class)->find($value[0]['orderId']);
                $order_change_log = $this->manager->getRepository(OrderChangeLog::class)->find($value[0]['id']);
                $change = $order_change_log->getChanges();
                unset($change['status']);
                $order_change_log->setChanges($change);
                $order->setStatus('paid');
                $this->manager->persist($order);
                $this->manager->flush();
                $this->manager->persist($order_change_log);
                $this->manager->flush();
            }
        }
        return 0;
    }
}
