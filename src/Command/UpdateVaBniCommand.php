<?php

namespace App\Command;

use App\Entity\Order;
use App\Entity\Bni;
use App\Repository\BniRepository;
use App\Repository\OrderRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use DateTime;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use App\Service\BniService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateVaBniCommand extends Command
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
            ->setName('app:update-va-bni')
            ->setDescription('Check update VA BNI')
            ->setHelp('This command allows you to check update va bni.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $trxId = 'masukan request id disini';
        $amount = 109500;
        $customerName = 'Andre ';

        $bniService = new BniService($this->logger);
        $bniService->updateVa($amount, $trxId, $customerName);
        return 0;
    }
}
