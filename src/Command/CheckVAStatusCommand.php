<?php

namespace App\Command;

use App\Helper\StaticHelper;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckVAStatusCommand extends Command
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
            ->setName('app:check-va-status')
            ->setDescription('Automatic checker for Virtual Account payment status.')
            ->setHelp('This command allows you to automatically check for the status of Virtual Account payment.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        StaticHelper::checkVAPaymentStatus($this->manager, $this->logger);

        return 1;
    }
}
