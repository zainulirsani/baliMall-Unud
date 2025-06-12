<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateProductSKUCommand extends Command
{
    protected static $defaultName = 'app:generate-product-sku';
    protected static $defaultDescription = 'Generate Product SKU';
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
        $products = $this->manager->getRepository(Product::class)->findBy(['sku' => null]);

        $this->logger->error('Generate Product SKU', [count($products)]);

        if (!empty($products)) {
            foreach ($products as $product) {
                $productId = $product->getId();

                $format = sprintf('BM-P-%s', $productId);

                $product->setSku($format);

                $this->manager->persist($product);
                $this->manager->flush();
            }

            return 1;
        }

        return 0;
    }
}
