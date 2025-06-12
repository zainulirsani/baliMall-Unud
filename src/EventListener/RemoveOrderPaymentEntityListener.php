<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Entity\OrderPayment;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\GenericEvent;

class RemoveOrderPaymentEntityListener implements ListenerInterface
{
    public function handle(GenericEvent $event): void
    {
        $orderPayment = $event->getSubject();

        if ($orderPayment instanceof OrderPayment && $event->hasArgument('em')) {
            /** @var ObjectManager $em */
            $em = $event->getArgument('em');
            /** @var Order $order */
            $order = $orderPayment->getOrder();
            /** @var User $buyer */
            $buyer = $order->getBuyer();
            $status = $buyer->getRole() === 'ROLE_USER_GOVERNMENT' ? 'pending_payment' : 'pending';

            $orderPayment->setOrder(null);
            $order->setStatus($status);

            $em->persist($orderPayment);
            $em->persist($order);
            $em->flush();

            $publicPath = getenv('APP_PUBLIC_PATH');
            $file = __DIR__.'/../../'.$publicPath.'/'.$orderPayment->getAttachment();
            $backup = __DIR__.'/../../'.$publicPath.'/'.$orderPayment->getAttachment().'.'.date('Ymd');

            if (is_file($file)) {
                //unlink($file);
                rename($file, $backup);
            }
        }
    }
}
