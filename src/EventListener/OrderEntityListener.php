<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ObjectManager;
use Hashids\Hashids;
use Symfony\Component\EventDispatcher\GenericEvent;

class OrderEntityListener implements ListenerInterface
{
    public function handle(GenericEvent $event): void
    {
        $order = $event->getSubject();

        if ($order instanceof Order && $event->hasArgument('em')) {
            /** @var ObjectManager $em */
            $em = $event->getArgument('em');
            $alphabet = getenv('HASHIDS_ALPHABET');
            $encoder = new Hashids(Order::class, 6, $alphabet);
            $hash = $encoder->encode($order->getId());
            $invoice = sprintf(getenv('BASE_INVOICE'), date('m'), date('Y'), $hash);

            /**
             * Re-check existing order to avoid duplicate invoice. A case happened on 29 Sept 2020.
             *
             * Invoice number(s) that has this issue:
             * - order_id 291 -> BM-INVOICE/09/2020/wap2On
             * - order_id 303 -> BM-INVOICE/09/2020/wap2on
             *
             * Notice the lowercase/uppercase letter
             */
            /** @var OrderRepository $repository */
            $repository = $em->getRepository(Order::class);
            /** @var Order $duplicate */
            $duplicate = $repository->findOneBy(['invoice' => $invoice]);

            $productCategoryRepository = $em->getRepository(ProductCategory::class);

            if ($duplicate instanceof Order) {
                $salt = 'App\Entity\DuplicateOrder-'.date('YmdHis');
                $duplicateEncoder = new Hashids($salt, 7, $alphabet);
                // Create new hash on different salt, to make sure no duplicate occur for the second time
                $hash = $duplicateEncoder->encode($order->getId());
                $invoice = sprintf(getenv('BASE_INVOICE'), date('m'), date('Y'), $hash);
            }

            $order->setInvoice($invoice);

            if ($event->hasArgument('items')) {
                $items = $event->getArgument('items');
                $requestTaxInvoice = $event->getArgument('request_tax_invoice');
                /** @var ProductRepository $repository */
                $repository = $em->getRepository(Product::class);

                $freeTaxCategoryList = [];

                if ($event->hasArgument('free_tax_category_list')) {
                    $freeTaxCategoryList = $event->getArgument('free_tax_category_list');
                }

                foreach ($items as $item) {
                    $tax = $event->getArgument('tax');
                    $quantity = $item['quantity'];
                    $price = $item['attributes']['price'];
                    $withTax = false;
                    $taxValue = 0;
                    $taxNominal = 0;

                    /** @var Product $product */
                    $product = $repository->findOneBy([
                        'id' => (int) $item['attributes']['image'],
                        'status' => 'publish',
                    ]);

                    if ($product instanceof Product && !empty($product->getStore())) {
                        if (in_array($product->getCategory(), $freeTaxCategoryList, false)) {
                            $tax = false;
                        }
                    }

                    if ($tax) {
                        $withTax = $item['attributes']['with_tax'] === 1;
                        $taxValue = $withTax ? $item['attributes']['tax_value'] : 0;
                        $taxNominal = $withTax ? $item['attributes']['tax_nominal'] : 0;
                    }

                    if ($product instanceof Product && !empty($product->getStore())) {
                        $orderProduct = new OrderProduct();
                        $orderProduct->setOrder($order);
                        $orderProduct->setProduct($product);
                        $orderProduct->setQuantity($quantity);
                        $orderProduct->setQuantityToSend($quantity);
                        // $orderProduct->setPrice(($price / 1.1)); // Statik 10%
                        $orderProduct->setPrice($price); // karena di keranjang belanja tanpa ppn
                        $orderProduct->setBasePrice((float) $product->getBasePrice());
                        // $orderProduct->setTotalPrice(($price / 1.1) * $quantity); // Statik 10%
                        $orderProduct->setTotalPrice($price * $quantity);// karena di keranjang belanja tanpa ppn
                        //$orderProduct->setNote();
                        $orderProduct->setWithTax($withTax);
                        $orderProduct->setTaxValue((float) $taxValue);
                        $orderProduct->setTaxNominal((float) $taxNominal);
                        $orderProduct->setOriginalId($product->getId());
                        $orderProduct->setOriginalName($product->getName());

                        $productFee = 0;

                        try {
                            $productCategory = $productCategoryRepository->find((int) $product->getCategory());
                            $productFee = $productCategory->getFee() ?? 0;
                        }catch (\Throwable $throwable){}

                        $orderProduct->setFee($productFee);

                        if ($requestTaxInvoice) {
                            $orderProduct->setNote('Request tax invoice');
                        }

                        // Decrement product quantity?
                        $stock = $product->getQuantity();
                        $newStock = $stock - $quantity;

                        $product->setQuantity($newStock < 1 ? 0 : $newStock);

                        $em->persist($orderProduct);
                        $em->persist($product);
                    }
                }

                $em->flush();
            }

            $em->persist($order);
            $em->flush();
        }
    }
}
