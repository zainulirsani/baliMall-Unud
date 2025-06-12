<?php

namespace App\EventListener;

use App\Entity\Voucher;
use App\Service\QrCodeGenerator;
use ErrorException;
use Symfony\Component\EventDispatcher\GenericEvent;

class RegenerateQrImageForVoucher implements ListenerInterface
{
    public function handle(GenericEvent $event): void
    {
        /** @var Voucher[] $vouchers */
        $vouchers = $event->getSubject();

        if (is_array($vouchers)
            && count($vouchers) > 0
            && $event->hasArgument('qrFactory')
            && $event->hasArgument('qrContent')) {
            /** @var QrCodeGenerator $qrFactory */
            $qrFactory = $event->getArgument('qrFactory');
            $qrContent = $event->getArgument('qrContent');

            foreach ($vouchers as $voucher) {
                $code = $voucher->getCode();

                $qrFactory->setTargetDirectory($code);

                try {
                    $qrFactory->generate(str_replace('--code--', $code, $qrContent));
                } catch (ErrorException $e) {
                    //
                }

                $qrFactory->resetTargetDirectory();
            }
        }
    }
}
