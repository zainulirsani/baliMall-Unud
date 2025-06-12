<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\Store;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InsertProductCategoriesToStoreCommand extends Command
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
            ->setName('app:insert-product-categories')
            ->setDescription('Fill selected product categories on existing merchants.')
            ->setHelp('This command allows you to automatically fill product_categories column on table store.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $productRepository = $this->manager->getRepository(Product::class);
        $storeRepository = $this->manager->getRepository(Store::class);
        $stores = $storeRepository->findAll();

        if (count($stores) > 0) {
            foreach ($stores as $store) {
                $products = $productRepository->getProductByStoreId($store->getId());
                $categories = [];

                if (count($products) > 0) {
                    foreach ($products as $product) {
                        if (!empty($product['pc_id'])) {
                            $categories[] = $product['pc_id'];
                        }
                    }
                }

                $store->setProductCategories($categories);
                $this->manager->persist($store);
            }

            $this->manager->flush();
        }

        return 1;



    }
}
