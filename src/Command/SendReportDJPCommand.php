<?php

namespace App\Command;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\DJPService;
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

class SendReportDJPCommand extends Command
{

    /** @var ObjectManager $manager */
    private $manager;
    protected $logger;

    public function __construct(Registry $registry, LoggerInterface $logger)
    {
        parent::__construct();

        $this->manager = $registry->getManager();
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:send-report-djp')
            ->setDescription('Automatic Send Report Fail DJP.')
            ->setHelp('This command allows you to resend djp.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var OrderRepository $repository */
        $repository = $this->manager->getRepository(Order::class);
        $orders     = $repository->findBy(['status'=>'paid','djpReportStatus'=>'djp_report_failed','taxType'=>58]);
        foreach ($orders as $order) {
            $this->logger->error(sprintf('Test DJP API Response: %s', json_encode($order->getSeller()->getUser()->getNpwp())));
            if (
                (
                    (!empty($order->getTaxDocumentNpwp()) && strlen(preg_replace('/[^0-9]/', '', $order->getTaxDocumentNpwp())) > 14) 
                    || (!empty($order->getBuyer()->getNpwp()) && strlen(preg_replace('/[^0-9]/', '', $order->getBuyer()->getNpwp())) > 14)
                ) 
                && !empty($order->getWorkUnitName()) 
                && !empty($order->getSeller()->getUser()->getNpwp())
                && strlen(preg_replace('/[^0-9]/', '', $order->getSeller()->getUser()->getNpwp())) > 14
                ) {
                $djpService = new DJPService($this->logger);
                $result_barang = $djpService->postTransations($order);
    
                $djpStatus = 'djp_report_failed';
    
                if ($result_barang['error'] === false) {
                    $djpStatus = 'djp_report_sent';
                    $order->setDjpResponseOrder(json_encode($result_barang));
                }
    
    
                $order->setDjpReportStatus($djpStatus);
                $this->manager->persist($order);
                $this->manager->flush();
            }
        }
        return 0;
    }
}
