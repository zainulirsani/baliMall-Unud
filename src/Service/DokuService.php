<?php


namespace App\Service;

use App\Exception\HttpClientException;
use App\Helper\StaticHelper;
use Psr\Log\LoggerInterface;
use App\Entity\ProductCategory;
use App\Repository\ProductCategoryRepository;

class DokuService
{
    private $baseUrl;
    private $endpoint;

    private $clientId;
    private $secretKey;
    private $contentType;
    private $logger;
    private $headers;
    private $paymentMethodTypes;

    private $artpayPaymentUrl;
    private $paymentUrl = '/checkout/v1/payment';
    private $statusUrl = '/orders/v1/status';
    private $pcRepository;

    public function __construct(LoggerInterface $logger, ProductCategoryRepository $pcRepository)
    {
        $this->baseUrl = getenv('JOKUL_API_URL');
        $this->artpayPaymentUrl = getenv('ARTPAY_API_URL');
        $this->artpayClientId = getenv('ARTPAY_CLIENT_ID');
        $this->clientId = getenv('JOKUL_CLIENT_ID');
        $this->secretKey = getenv('JOKUL_SECRET_KEY');
        $this->contentType = 'application/json';
        $this->logger = $logger;
        $this->pcRepository = $pcRepository;

        $this->headers = [
            'Client-Id' => $this->clientId,
        ];

        $this->paymentMethodTypes = [
            'VIRTUAL_ACCOUNT_BCA',
            'VIRTUAL_ACCOUNT_BNI',
            'VIRTUAL_ACCOUNT_BANK_MANDIRI',
            'VIRTUAL_ACCOUNT_BANK_SYARIAH_MANDIRI',
            'VIRTUAL_ACCOUNT_DOKU',
            'QRIS',
            'CREDIT_CARD'
        ];
    }

    public function requestStatus($invoiceNumber, $requestId) : array
    {
        $result = [
            'status' => false,
            'data' => []
        ];

        $dateTime = gmdate("Y-m-d H:i:s");
        $dateTime = date(DATE_ISO8601, strtotime($dateTime));
        $timestamp = substr($dateTime, 0, 19) . "Z";

        $componentSignature = "Client-Id:" . $this->clientId . "\n" .
            "Request-Id:" . $requestId. "\n" .
            "Request-Timestamp:" . $timestamp . "\n" .
            "Request-Target:" . $this->statusUrl . '/' . $invoiceNumber;

        $signature = base64_encode(hash_hmac('sha256', $componentSignature, $this->secretKey, true));

        $this->headers['Request-Id'] = $requestId;
        $this->headers['Request-Timestamp'] = $timestamp;
        $this->headers['Signature'] = 'HMACSHA256=' . $signature;

        $this->endpoint = $this->baseUrl . $this->statusUrl . '/' . $invoiceNumber;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Signature:' . 'HMACSHA256=' . $signature,
            'Request-Id:' . $requestId,
            'Client-Id:' . $this->clientId,
            'Request-Timestamp:' . $timestamp,
            // 'Request-Target:' . $this->statusUrl,
        ));

        $responseJson = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (is_string($responseJson) && $httpcode == 200) {
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('DOKU API GET STATUS result: %s', json_encode($responseJson)));

            $response = json_decode($responseJson, true);

            $result['status'] = true;
            $result['data'] = $response;

        } else {
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('DOKU API GET STATUS result: %s', json_encode($responseJson)));
        }

        return $result;
    }

    public function requestPaymentArtpay($invoiceNumber, $requestId, $orders, $nominal)
    {
        $result = [
            'status' => false,
            'data' => []
        ];

        $requestBody = $this->constructPayloadArtpay($invoiceNumber, $orders, $nominal);
        $dateTime = gmdate("Y-m-d H:i:s");
        $dateTime = date(DATE_ISO8601, strtotime($dateTime));
        $timestamp = substr($dateTime, 0, 19) . "Z";

        // dd($requestBody);
        // $digestValue = base64_encode(hash('sha256', json_encode($requestBody), true));

        // $componentSignature = "Client-Id:" . $this->clientId . "\n" .
        //     "Request-Id:" . $requestId. "\n" .
        //     "Request-Timestamp:" . $timestamp . "\n" .
        //     "Request-Target:" . $this->paymentUrl . "\n" .
        //     "Digest:" . $digestValue;

        // $signature = base64_encode(hash_hmac('sha256', $componentSignature, $this->secretKey, true));

        // $this->headers['Request-Id'] = $requestId;
        // $this->headers['Request-Timestamp'] = $timestamp;
        // $this->headers['Signature'] = 'HMACSHA256=' . $signature;

        $this->endpoint = $this->artpayPaymentUrl;

        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Client-Id: '.$this->artpayClientId,
        ));

        $responseJson = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);
        // dd($httpcode, $this->endpoint, $this->artpayClientId, $responseJson, $requestBody);
        if (is_string($responseJson) && ($httpcode == 200 || $httpcode == 201)) {
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('ARTPAY API result: %s', json_encode($responseJson)));

            $response = json_decode($responseJson, true);

            $result['status'] = true;
            $result['data'] = $response;

        } else {
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('ARTPAY API result: %s', json_encode($responseJson)));
        }

        return $result;
    }


    public function requestPayment($invoiceNumber, $requestId, $orders, $nominal) : array
    {
        $result = [
            'status' => false,
            'data' => []
        ];

        $requestBody = $this->constructPayload($invoiceNumber, $orders, $nominal);

        $dateTime = gmdate("Y-m-d H:i:s");
        $dateTime = date(DATE_ISO8601, strtotime($dateTime));
        $timestamp = substr($dateTime, 0, 19) . "Z";

        $digestValue = base64_encode(hash('sha256', json_encode($requestBody), true));

        $componentSignature = "Client-Id:" . $this->clientId . "\n" .
            "Request-Id:" . $requestId. "\n" .
            "Request-Timestamp:" . $timestamp . "\n" .
            "Request-Target:" . $this->paymentUrl . "\n" .
            "Digest:" . $digestValue;

        $signature = base64_encode(hash_hmac('sha256', $componentSignature, $this->secretKey, true));

        $this->headers['Request-Id'] = $requestId;
        $this->headers['Request-Timestamp'] = $timestamp;
        $this->headers['Signature'] = 'HMACSHA256=' . $signature;

        $this->endpoint = $this->baseUrl . $this->paymentUrl;

        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Signature:' . 'HMACSHA256=' . $signature,
            'Request-Id:' . $requestId,
            'Client-Id:' . $this->clientId,
            'Request-Timestamp:' . $timestamp,
            'Request-Target:' . $this->paymentUrl,
        ));

        $responseJson = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        // dd($httpcode, $responseJson, $requestBody);

        if (is_string($responseJson) && $httpcode == 200) {
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('DOKU API result: %s', json_encode($responseJson)));

            $response = json_decode($responseJson, true);

            $result['status'] = true;
            $result['data'] = $response;

        } else {
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('DOKU API result: %s', json_encode($responseJson)));
        }

        return $result;
    }

    public function constructPayload($invoiceNumber, $orders, $nominal) :array
    {
        $amount = $nominal;
        $sharedInvoice = $orders[0]->getSharedInvoice();
        $items = [];

        // $callbackUrl = getenv('APP_URL').'/user/order/shared/' .base64_encode('bm-order:'.$sharedInvoice);
        $callbackUrl = getenv('APP_URL').'/user/ppk-treasurer/dashboard';

        foreach ($orders as $order) {
            $orderProducts = $order->getOrderProducts();
            $store = $order->getSeller();

            foreach ($orderProducts as $item) {

                $price = $item->getWithTax() ? ($item->getPrice() + $item->getTaxNominal()) : $item->getPrice();

                $items[] = [
                    'name' => $item->getProduct()->getName(),
                    'price' => (float) $price,
                    'quantity' => $item->getQuantity()
                ];
            }
        }

        $payment = [
            // 'payment_due_date' => 60,
            'payment_due_date' => ((60 * 24) * 2),
            'payment_method_types' => $this->paymentMethodTypes
        ];

        $buyer = $orders[0]->getBuyer();

        $customer = [
            'id' => $orders[0]->getPpkId(),
            'name' => preg_replace("/[^A-Za-z0-9 ]/", "", str_replace('@gmail.com','',$orders[0]->getPpkName())),
            'email' => $orders[0]->getPpkEmail(),
            'phone' => $buyer->getPhoneNumber(),
            'address' => $orders[0]->getAddress(),
            'country' => 'ID'
        ];

        $orderPayload = [
            'order' => [
                'amount' => $amount,
                'invoice_number' => $invoiceNumber,
//                'line_items' => $items,
                'currency' => 'IDR',
                'callback_url' => $callbackUrl
            ],
            'payment' => $payment,
            'customer' => $customer
        ];

        return $orderPayload;
    }

    public function constructPayloadArtpay($invoiceNumber, $orders, $nominal) :array
    {
        $amount = $nominal;
        $sharedInvoice = $orders[0]->getSharedInvoice();
        $items = [];

        // $callbackUrl = getenv('APP_URL').'/user/order/shared/' .base64_encode('bm-order:'.$sharedInvoice);
        $callbackUrl = getenv('APP_URL').'/user/ppk-treasurer/dashboard';
        foreach ($orders as $order) {
            $orderProducts = $order->getOrderProducts();
            $store = $order->getSeller();
            foreach ($orderProducts as $item) {
                $price = $item->getWithTax() ? $item->getPrice() + ($item->getTaxNominal() / $item->getQuantity()) : $item->getPrice();
                $category = $this->pcRepository->find($item->getProduct()->getCategory());
                $product_url = getenv('APP_URL').'/'.$item->getProduct()->getStore()->getSlug().'/'.$item->getProduct()->getSlug();
                $items[] = [
                    'name' => $item->getProduct()->getName(),
                    'quantity' => (int) $item->getQuantity(),
                    'price' => (float) $price,
                    'sku' => $item->getProduct()->getSku(),
                    'category' => $category->getName(),
                    'url' => $product_url
                ];
            }
            if ($order->getShippingPrice() > 0) {
                $items[] = [
                    'name' => 'Delivery Cost',
                    'quantity' => 1,
                    'price' => (float) $order->getShippingPrice(),
                    'sku' => 'shipping_'.$order->getInvoice(),
                    'category' => 'Shipping',
                    'url' => 'https://example.com'
                ];
            }
            if ($order->getTaxType() == '59' && $order->getTotal() + $order->getShippingPrice() > 2220000) {
                $pphTreasurer  = $order->getTaxType() == '59' ? (!empty($order->getTreasurerPphNominal()) ? $order->getTreasurerPphNominal(): 0): 0;
                $ppnTreasurer  = $order->getTaxType() == '59' ? (!empty($order->getTreasurerPpnNominal()) ? $order->getTreasurerPpnNominal(): 0): 0;
                if ($pphTreasurer != 0) {
                    $items[] = [
                        'name' => 'PPH',
                        'quantity' => 1,
                        'price' => (float) ($pphTreasurer * -1),
                        'sku' => 'pph_pmk59_'.$order->getInvoice(),
                        'category' => 'Tax',
                        'url' => 'https://example.com'
                    ];
                }
                if ($ppnTreasurer != 0) {
                    $items[] = [
                        'name' => 'PPN PMK 59 Invoice '.$order->getInvoice(),
                        'quantity' => 1,
                        'price' => (float) ($ppnTreasurer * -1),
                        'sku' => 'ppn_pmk59_'.$order->getInvoice(),
                        'category' => 'Tax',
                        'url' => 'https://example.com'
                    ];
                }
            }
        }

        $payment = [
            'payment_due_date' => 30,
            // 'payment_due_date' => ((60 * 24) * 2),
            // 'payment_method_types' => $this->paymentMethodTypes
        ];

        $buyer = $orders[0]->getBuyer();

        $customer = [
            'id' => $orders[0]->getPpkId(),
            'name' => str_replace('@gmail.com','',$orders[0]->getPpkName()),
            'phone' => $buyer->getPhoneNumber(),
            'email' => $orders[0]->getPpkEmail(),
            'address' => $orders[0]->getAddress(),
            'postcode' => $orders[0]->getPostCode(),
            'state' => $orders[0]->getCity(),
            'city' => $orders[0]->getDistrict() != null ? $orders[0]->getDistrict(): $orders[0]->getCity(),
            'country' => 'ID'
        ];

        $shipping_address = [
            'first_name' => str_replace('@gmail.com','',$orders[0]->getPpkName()),
            'last_name' => str_replace('@gmail.com','',$orders[0]->getPpkName()),
            'address' => $orders[0]->getAddress(),
            'city' => $orders[0]->getCity(),
            'postal_code' => $orders[0]->getPostCode(),
            'phone' => $buyer->getPhoneNumber(),
            'country_code' => 'IDN'
        ];

        $orderPayload = [
            'order' => [
                'checkout_date' => date('Y-m-d H:i:s'),
                'amount' => $amount,
                'invoice_number' => $orders[0]->getSharedInvoice(),
                'billing_number' => $invoiceNumber,
                'currency' => 'IDR',
                'callback_url' => $callbackUrl,
                'language' => 'EN',
                'auto_redirect' => true,
                'disable_retry_payment' => true,
                'line_items' => $items
            ],
            'payment' => $payment,
            'customer' => $customer,
            'shipping_address' => $shipping_address,
            'additional_info' => [
                'allow_tenor' => [0,3,6,12],
                "close_redirect" => "www.doku.com",
                "doku_wallet_notify_url" => "https://example.com"
            ]
        ];

        return $orderPayload;
    }
}
