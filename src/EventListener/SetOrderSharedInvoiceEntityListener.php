<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\Persistence\ObjectManager;
use Hashids\Hashids;
use Symfony\Component\EventDispatcher\GenericEvent;

class SetOrderSharedInvoiceEntityListener implements ListenerInterface
{
    public function handle(GenericEvent $event): void
    {
        $repository = $event->getSubject();

        if ($repository instanceof OrderRepository && $event->hasArgument('em')) {
            // ['v1' => 16, 'v2' => 18]
            $encoder = new Hashids(__CLASS__, 18, getenv('HASHIDS_ALPHABET'));
            /** @var ObjectManager $em */
            $em = $event->getArgument('em');
            $runType = $event->hasArgument('run_type') ? $event->getArgument('run_type') : 'batch';

            if ($runType === 'single') {
                /** @var Order[] $orders */
                $orders = $event->hasArgument('orders') ? $event->getArgument('orders') : [];
                $ids = [];

                if (count($orders) > 0) {
                    foreach ($orders as $order) {
                        $ids[] = (int) $order->getId();
                    }

                    $replace = $encoder->encode($ids);

                    foreach ($orders as $order) {
                        $invoice = $order->getInvoice();
                        $parts = explode('/', $invoice);
                        $search = end($parts);
                        $sharedInvoice = str_replace($search, $replace, $invoice);

                        $order->setSharedInvoice($sharedInvoice);
                        $em->persist($order);
                    }

                    $em->flush();
                }
            } elseif ($runType === 'b2g_batch') {
                /** @var Order[] $orders */
                $orders = $event->hasArgument('orders') ? $event->getArgument('orders') : [];

                if (count($orders) > 0) {
                    foreach ($orders as $order) {
                        $ids = [(int) $order->getId()];
                        $invoice = $order->getInvoice();
                        $parts = explode('/', $invoice);
                        $search = end($parts);
                        $sharedInvoice = str_replace($search, $encoder->encode($ids), $invoice);

                        $order->setSharedInvoice($sharedInvoice);
                        $em->persist($order);
                    }

                    $em->flush();
                }
            } else {
                /** @var Order[] $orders */
                $orders = $repository->getOrdersBySharedId();

                foreach ($orders as $order) {
                    $ids = [(int) $order->getId()];
                    $invoice = $order->getInvoice();
                    $parts = explode('/', $invoice);
                    $search = end($parts);
                    /** @var Order[] $related */
                    $related = $repository->getOrdersBySharedId($order->getSharedId(), $order->getId(), false);

                    foreach ($related as $item) {
                        $ids[] = (int) $item->getId();
                    }

                    $sharedInvoice = str_replace($search, $encoder->encode($ids), $invoice);

                    foreach ($related as $item) {
                        $item->setSharedInvoice($sharedInvoice);
                        $em->persist($item);
                    }

                    $order->setSharedInvoice($sharedInvoice);
                    $em->persist($order);
                }

                $em->flush();
            }
        }
    }
}
