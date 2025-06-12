<?php

namespace App\EventListener;

use App\Entity\Voucher;
use App\Repository\VoucherRepository;
use App\Service\QrCodeGenerator;
use Doctrine\Persistence\ObjectManager;
use ErrorException;
use Hashids\Hashids;
use Symfony\Component\EventDispatcher\GenericEvent;

class MassVoucherEntityListener implements ListenerInterface
{
    public function handle(GenericEvent $event): void
    {
        /** @var Voucher[] $vouchers */
        $vouchers = $event->getSubject();

        if (is_array($vouchers)
            && count($vouchers) > 0
            && $event->hasArgument('em')
            && $event->hasArgument('qrFactory')
            && $event->hasArgument('qrContent')) {
            /** @var ObjectManager $em */
            $em = $event->getArgument('em');
            $alphabet = getenv('HASHIDS_ALPHABET');
            $encoder = new Hashids(Voucher::class, 8, $alphabet);
            /** @var QrCodeGenerator $qrFactory */
            $qrFactory = $event->getArgument('qrFactory');
            $qrContent = $event->getArgument('qrContent');
            /** @var VoucherRepository $repository */
            $repository = $em->getRepository(Voucher::class);

            foreach ($vouchers as $voucher) {
                $code = $encoder->encode($voucher->getId());
                /** @var Voucher $duplicate */
                $duplicate = $repository->findOneBy(['code' => $code]);

                if ($duplicate instanceof Voucher) {
                    $salt = 'App\Entity\DuplicateVoucher-'.date('YmdHis');
                    $duplicateEncoder = new Hashids($salt, 9, $alphabet);
                    $code = $duplicateEncoder->encode($voucher->getId());
                }

                $voucher->setCode($code);
                $qrFactory->setTargetDirectory($code);

                try {
                    $qrImage = $qrFactory->generate(str_replace('--code--', $code, $qrContent));

                    $voucher->setQrImage($qrImage);
                } catch (ErrorException $e) {
                    //
                }

                $qrFactory->resetTargetDirectory();
                $em->persist($voucher);
            }

            $em->flush();
        }
    }
}
