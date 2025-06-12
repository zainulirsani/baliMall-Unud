<?php


namespace App\EventListener;


use App\Entity\Disbursement;
use Symfony\Component\EventDispatcher\GenericEvent;

class OrderDisbursementListener implements ListenerInterface
{
    public function handle(GenericEvent $event): void
    {
        if ($event->hasArgument('order') && $event->hasArgument('em')) {
            $em       = $event->getArgument('em');
            $order    = $event->getArgument('order');
            $products = $order->getOrderProducts();
            $store    = $order->getSeller();

            if (count($products) > 0) {
                $totalFee = 0;
                $totalProductPrice = 0;

                foreach ($products as $product) {
                    $fee = (int) empty($product->getFee()) ? 0 : $product->getFee();

                    $totalPrice = $product->getTotalPrice();
                    $price_no_task = $totalPrice;
                     if ($product->getWithTax() && $product->getTaxNominal() > 0) {
                         $totalPrice += $product->getTaxNominal();
                     }

                    $totalProductPrice += $totalPrice;
                    $product_fee = ($price_no_task * $fee) / 100;
                    $totalFee += $product_fee;
                }

                $disbursementRepository = $event->getArgument('disbursement');
                $cek_data = $disbursementRepository->findOneBy(['orderId' => $order->getId()]);
                if (empty($cek_data)) {
                    $disbursement = new Disbursement();
                    $disbursement->setOrderId($order->getId());
                    $disbursement->setProductFee($totalFee);
                    $disbursement->setStatus('pending');
                    $disbursement->setRekeningName($store->getRekeningName());
                    $disbursement->setBankName($store->getBankName());
                    $disbursement->setNomorRekening($store->getNomorRekening());
                    $disbursement->setTotalProductPrice($totalProductPrice);
                    $disbursement->setOrderShippingPrice($order->getShippingPrice());

                    $em->persist($disbursement);
                    $em->flush();
                }
            }
        }
    }
}
