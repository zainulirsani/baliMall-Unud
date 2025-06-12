<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
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

class GeneratePpkTreasurerOrderCommand extends Command
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
            ->setName('app:generate-ppk-treasurer-order')
            ->setDescription('Make user account from table user_ppk_treasurer')
            ->setHelp('This command allows you to generate user ppk treasurer.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var OrderRepository $repository */
        $repository     = $this->manager->getRepository(Order::class);
        $userRepository = $this->manager->getRepository(User::class);
        $orders         = $repository->getOrderPpkNameNotNull();
        foreach ($orders as $key => $order) {
            if (!empty($order->getPpkEmail()) && !empty($order->getTreasurerEmail())) {
                $ppk       = $userRepository->findOneBy(['email' => $order->getPpkEmail()]);
                $treasurer = $userRepository->findOneBy(['email' => $order->getTreasurerEmail()]);
                if ($ppk != null) {
                    $order->setPpkId($ppk->getId());
                }
                if ($treasurer != null) {
                    $order->setTreasurerId($treasurer->getId());
                }
                $this->manager->persist($order);
                $this->manager->flush();
            }
        }
        return 0;
    }
}
