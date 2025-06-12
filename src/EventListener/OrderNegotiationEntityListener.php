<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Entity\OrderNegotiation;
use App\Entity\Product;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\GenericEvent;

class OrderNegotiationEntityListener implements ListenerInterface
{
    public function handle(GenericEvent $event): void
    {
        $order = $event->getSubject();

        if ($order instanceof Order && $event->hasArgument('em')) {
            /** @var ObjectManager $em */
            $em = $event->getArgument('em');
            $buyerId = $event->hasArgument('buyer_id') ? $event->getArgument('buyer_id') : 0;
            //$items = $event->hasArgument('items') ? $event->getArgument('items') : [];
            $negotiation = $event->hasArgument('negotiation') ? $event->getArgument('negotiation') : [];
            $withTax = $event->hasArgument('tax') ? $event->getArgument('tax') : false;

            $productRepository = $em->getRepository(Product::class);

            if ($buyerId > 0 && count($negotiation) > 0) {
                $executionTime = $negotiation['time'];
                $note = $negotiation['note'];
                $quantity = $negotiation['quantity'];
                $shippingPriceWithTax = $shippingPrice = $negotiation['shipping'];
                $hasNegotiation = $tax = $addTax = $normalizeTax = $shippingTax = 0;

                if ($withTax) {
                    $tax = $negotiation['tax'];
                    $addTax = $tax / 100;
                    $normalizeTax = (100 + $tax) / 100;
                    $shippingPrice = $shippingPriceWithTax / $normalizeTax; // Exclude tax
                    $shippingTax = $shippingPriceWithTax - ($shippingPriceWithTax / $normalizeTax); // Only Tax
                }

                $indexQty = 0;
                foreach ($negotiation['price'] as $key => $price) {
                    $freeTax = false;

                    if ($event->hasArgument('free_tax_category_list')) {

                        $freeTaxCategoryList = $event->getArgument('free_tax_category_list');

                        $product = $productRepository->findOneBy([
                            'id' => (int) $key
                        ]);

                        if ($product instanceof Product) {
                            if (in_array($product->getCategory(), $freeTaxCategoryList, false)) {
                                 $freeTax = true;
                            }
                        }
                    }

                    if ($withTax && $freeTax === false) {
                        $negotiatedPrice = (float) ($price / $normalizeTax); // Exclude tax
                    } else {
                        $negotiatedPrice = (float) $price;
                    }

                    // Find price for 1 item
                    $negotiatedPrice /= $quantity[$indexQty];

                    $orderNegotiation = new OrderNegotiation();
                    $orderNegotiation->setOrder($order);
                    $orderNegotiation->setProductId($key);
                    $orderNegotiation->setSubmittedBy($buyerId);
                    $orderNegotiation->setSubmittedAs('buyer');
                    $orderNegotiation->setNegotiatedPrice(round($negotiatedPrice));
                    $orderNegotiation->setTaxNominalPrice( $freeTax ? 0 : round($negotiatedPrice * $addTax, 1));
                    $orderNegotiation->setExecutionTime($executionTime);
                    $orderNegotiation->setNegotiatedShippingPrice($shippingPrice);
                    $orderNegotiation->setIsApproved(false);
                    $orderNegotiation->setBatch(1);
                    $orderNegotiation->setNote($note);
                    $orderNegotiation->setTaxNominalShipping((float) $shippingTax);
                    $orderNegotiation->setTaxValue($freeTax ? 0 : (float) $tax);

                    $em->persist($orderNegotiation);

                    $hasNegotiation++;
                    $indexQty++;
                }

                if ($hasNegotiation > 0) {
                    $order->setNegotiationStatus('pending');

                    $em->persist($order);
                    $em->flush();
                }
            }
        }
    }
}
