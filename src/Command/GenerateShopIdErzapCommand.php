<?php

namespace App\Command;

use App\Entity\Store;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateShopIdErzapCommand extends Command
{
    protected static $defaultName = 'app:generate-shop-id';
    protected static $defaultDescription = 'Generate Shop ID';
    protected $manager;
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
            ->setDescription(self::$defaultDescription)
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stores = $this->manager->getRepository(Store::class)->findAll();
        foreach ($stores as $key => $value) {
            $createdAt = date_format($value->getCreatedAt(), 'dmy');
            $shop_id = 'BM-'.$createdAt.'-'.$value->getId();
            $value->setShopId($shop_id);
            
            $this->manager->persist($value);
            $this->manager->flush();
        }
        return 0;
    }
}
