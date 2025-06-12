<?php

namespace App\Service;

use Midtrans;
use Psr\Log\LoggerInterface;

class MidtransService
{

    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        Midtrans\Config::$serverKey = getenv('MIDTRANS_SERVER_KEY');
        Midtrans\Config::$isProduction = getenv('APP_URL') === 'https://tokodaring.balimall.id';
        Midtrans\Config::$isSanitized = true;
        Midtrans\Config::$is3ds = true;

        $this->logger = $logger;
    }

    public function requestPayment($nominal, $orders, $orderId): array
    {
        $result = [
            'status' => false,
            'data' => [],
        ];

        $buyer = $orders[0]->getBuyer();
        $itemDetails = [];

        foreach ($orders as $order) {
            $orderProducts = $order->getOrderProducts();
            $store = $order->getSeller();

            foreach ($orderProducts as $item) {

                $price = $item->getWithTax() === true ? $item->getPrice() * $this->getPpnPercentage($store->getUmkmCategory()) : $item->getPrice();

                $itemDetails[] = [
                    'name' => $item->getProduct()->getName(),
                    'price' => (float) $price,
                    'quantity' => $item->getQuantity()
                ];
            }
        }

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $nominal,
            ],
            'customer_details' => [
                'first_name' => $buyer->getFirstName(),
                'last_name' => $buyer->getLastName(),
                'email' => $buyer->getEmail(),
                'phone' => $buyer->getPhoneNumber(),
            ],
//            'item_details' => $itemDetails
        ];

        try {
            $snapToken = Midtrans\Snap::getSnapToken($params);

            $result['status'] = true;
            $result['data']['token'] = $snapToken;

            $this->logger->error('Midtrans result = ', $result);

        }catch (\Throwable $throwable) {
            $this->logger->error('Error requesting snap token midtrans ', [$throwable->getMessage()]);
        }

        return $result;
    }

    public function verifyNotification(array $notificationBody): bool
    {
        try {
            $orderId = $notificationBody['order_id'];
            $statusCode = $notificationBody['status_code'];
            $grossAmount = $notificationBody['gross_amount'];
            $serverKey = getenv('MIDTRANS_SERVER_KEY');

            $signature = hash('sha512', sprintf('%s%s%s%s', $orderId, $statusCode, $grossAmount, $serverKey));

            $requestSignature = $notificationBody['signature_key'];

            if ($signature === $requestSignature) {
                return true;
            }

            return false;

        }catch (\Throwable $throwable) {
            $this->logger->error('Midtrans-failed validate signature ', [$notificationBody]);

            return false;
        }
    }
}
