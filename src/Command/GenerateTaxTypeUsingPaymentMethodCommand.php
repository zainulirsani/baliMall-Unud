<?php

namespace App\Command;

use App\Entity\Order;
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

class GenerateTaxTypeUsingPaymentMethodCommand extends Command
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
            ->setName('app:generate-tax-type-using-payment-method')
            ->setDescription('Generate tax type using payment method')
            ->setHelp('This command allows you to generate user ppk treasurer.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var OrderRepository $repository */
        $repository     = $this->manager->getRepository(Order::class);
        $orders         = $repository->getOrderProcessPaymentOrPaid();
        foreach ($orders as $key => $order) {
            $payment_method = $order->getPpkPaymentMethod();
            if ($payment_method == 'uang_persediaan') {
                $order->setTaxType(58);
            } else {
                $order->setTaxType(59);
            }
            $this->manager->persist($order);
            $this->manager->flush();
        }
        return 0;
    }
}
