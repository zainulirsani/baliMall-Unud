<?php

namespace App\Helper;

use App\Entity\Notification;
use App\Entity\Order;
use App\Entity\OrderPayment;
use App\Entity\Store;
use App\Entity\User;
use App\Entity\VirtualAccount;
use App\Repository\OrderPaymentRepository;
use App\Repository\OrderRepository;
use App\Repository\VirtualAccountRepository;
use App\Service\WSClientBPD;
use App\Utility\GoogleMailHandler;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Psr\Log\LoggerInterface;
use RandomLib\Factory;

class StaticHelper
{
    public static function removeDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            $files = glob($directory.'/*');

            foreach ($files as $file) {
                is_dir($file) ? self::removeDirectory($file) : unlink($file);
            }

            rmdir($directory);
        }
    }

    public static function splitFullName(string $name): array
    {
        $fullName = ltrim(filter_var($name, FILTER_SANITIZE_STRING));
        $names = explode(' ', $fullName);
        $firstName = $names[0] ?? '';

        if (isset($names[0])) {
            unset($names[0]);
        }

        $username = isset($names[1]) ? $firstName.'_'.$names[1] : $firstName;

        return [
            'first_name' => $firstName,
            'last_name' => trim(implode(' ', $names)),
            'username' => strtolower(trim(mb_strimwidth($username, 0, 20))),
        ];
    }

    public static function secureRandomCode(int $length = 12): string
    {
        try {
            return bin2hex(random_bytes($length));
        } catch (Exception $e) {
        }

        return self::generateStr();
    }

    public static function checkEmailCanonical(string $email): string
    {
        return GoogleMailHandler::validate($email);
    }

    public static function createSlug(string $string, string $separator = '-'): string
    {
        $string = preg_replace('~[^\pL\d]+~u', $separator, $string);
//        $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);
        $string = preg_replace('~[^-\w]+~', '', $string);
        $string = trim($string, $separator);
        $string = preg_replace('~-+~', $separator, $string);
        $string = strtolower($string);

        return $string;
    }

    public static function generateInt(int $min = 1, int $max = 10000, string $strength = 'low'): int
    {
        $factory = new Factory();

        if ($strength === 'high') {
            $generator = $factory->getHighStrengthGenerator();
        } elseif ($strength === 'medium') {
            $generator = $factory->getMediumStrengthGenerator();
        } else {
            $generator = $factory->getLowStrengthGenerator();
        }

        return $generator->generateInt($min, $max);
    }

    public static function generateStr(int $length = 32, string $strength = 'low'): string
    {
        $factory = new Factory();
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ($strength === 'high') {
            $generator = $factory->getHighStrengthGenerator();
        } elseif ($strength === 'medium') {
            $generator = $factory->getMediumStrengthGenerator();
        } else {
            $generator = $factory->getLowStrengthGenerator();
        }

        return $generator->generateString($length, $characters);
    }

    public static function formatForCurrency($number, $decimal = 1, $decimalPoint = ',', $thousandSeparator = '.'): string
    {
        $tmp =  number_format($number, $decimal, $decimalPoint, $thousandSeparator);

        return rtrim(rtrim($tmp, 0),',');
    }

    public static function checkVAPaymentStatus(ObjectManager $manager, ?LoggerInterface $logger = null): int
    {
        $wsClient = new WSClientBPD();
        /** @var VirtualAccountRepository $repository */
        $repository = $manager->getRepository(VirtualAccount::class);
        /** @var VirtualAccount[] $payments */
        $payments = $repository->findBy(['paidStatus' => '0'], null, 50);
        /** @var OrderRepository $orderRepository */
        $orderRepository = $manager->getRepository(Order::class);
        /** @var OrderPaymentRepository $paymentRepository */
        $paymentRepository = $manager->getRepository(OrderPayment::class);
        $processed = 0;

        foreach ($payments as $payment) {
            try {
                $response = $wsClient->billInquiry($payment->getBillNumber());

                if ($logger instanceof LoggerInterface) {
                    $logger->error('VA response on payment check from cron!', $response);
                }

                if ($response['status'] && $response['code'] === '00' && $response['data'][0]['sts_bayar'] === '1') {
                    $payment->setPaidStatus('1');
                    $payment->setResponse(json_encode($response['data']));

                    $manager->persist($payment);
                    $processed++;

                    $orders = $orderRepository->findBy(['sharedInvoice' => $payment->getInvoice()]);

                    foreach ($orders as $order) {
                        /** @var Store $store */
                        $store = $order->getSeller();
                        /** @var User $seller */
                        $seller = $store->getUser();
                        /** @var User $buyer */
                        $buyer = $order->getBuyer();

                        $order->setStatus($order->getIsB2gTransaction() ? 'payment_process' : 'paid');

                        /** @var OrderPayment $orderPayment */
                        $orderPayment = $paymentRepository->findOneBy(['invoice' => $order->getInvoice()]);

                        if (!$orderPayment instanceof OrderPayment) {
                            $orderPayment = new OrderPayment();
                        }

                        $orderPayment->setOrder($order);
                        $orderPayment->setInvoice($order->getInvoice());
                        $orderPayment->setName($order->getName());
                        $orderPayment->setEmail($order->getEmail());
                        $orderPayment->setType('virtual_account');
                        $orderPayment->setAttachment($payment->getReferenceId());
                        $orderPayment->setNominal($order->getTotal() + $order->getShippingPrice());
                        $orderPayment->setMessage('Pembayaran menggunakan Virtual Account');
                        $orderPayment->setBankName('bpd_bali');

                        try {
                            $orderPayment->setDate(new DateTime('now'));
                        } catch (Exception $e) {
                        }

                        $notification = new Notification();
                        $notification->setSellerId($seller->getId());
                        $notification->setBuyerId($buyer->getId());
                        $notification->setIsSentToSeller(false);
                        $notification->setIsSentToBuyer(false);
                        $notification->setTitle('Order Status');
                        $notification->setContent(sprintf('Order Status No Invoice : %s, %s', $order->getInvoice(), 'paid'));

                        $manager->persist($order);
                        $manager->persist($orderPayment);
                        $manager->persist($notification);
                        $manager->flush();
                    }
                }
            } catch (Exception $e) {
                $logger->error(sprintf('VA exception handler from %s!', __METHOD__), ['message' => $e->getMessage()]);
            }
        }

        if ($processed > 0) {
            $manager->flush();
        }

        return $processed;
    }
}
