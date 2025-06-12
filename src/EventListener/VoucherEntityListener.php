<?php

namespace App\EventListener;

use App\Entity\Voucher;
use App\Service\QrCodeGenerator;
use Doctrine\Persistence\ObjectManager;
use ErrorException;
use Hashids\Hashids;
use Symfony\Component\EventDispatcher\GenericEvent;

class VoucherEntityListener implements ListenerInterface
{
    public function handle(GenericEvent $event): void
    {
        $voucher = $event->getSubject();

        if ($voucher instanceof Voucher && $event->hasArgument('em')) {
            /** @var ObjectManager $em */
            $em = $event->getArgument('em');
            $alphabet = getenv('HASHIDS_ALPHABET');
            $encoder = new Hashids(Voucher::class, 8, $alphabet);
            $code = $encoder->encode($voucher->getId());
            /** @var Voucher $duplicate */
            $duplicate = $em->getRepository(Voucher::class)->findOneBy(['code' => $code]);

            if ($duplicate instanceof Voucher) {
                $salt = 'App\Entity\DuplicateVoucher-'.date('YmdHis');
                $duplicateEncoder = new Hashids($salt, 9, $alphabet);
                $code = $duplicateEncoder->encode($voucher->getId());
            }

            $voucher->setCode($code);

            // Generate QR Image
            if ($event->hasArgument('qrFactory') && $event->hasArgument('qrContent')) {
                /** @var QrCodeGenerator $qrFactory */
                $qrFactory = $event->getArgument('qrFactory');
                $qrFactory->setTargetDirectory($code);

                try {
                    $qrContent = $event->getArgument('qrContent');
                    $qrContent = str_replace('--code--', $code, $qrContent);
                    $qrImage = $qrFactory->generate($qrContent);

                    $voucher->setQrImage($qrImage);
                } catch (ErrorException $e) {
                    //
                }
            }

            $em->persist($voucher);
            $em->flush();
        }
    }
}
