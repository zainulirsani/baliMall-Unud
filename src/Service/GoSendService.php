<?php


namespace App\Service;

use App\Exception\HttpClientException;
use Psr\Log\LoggerInterface;

class GoSendService
{
    private $baseUrl;
    private $endpoint;
    private $estimateUrl;
    private $createBookingUrl;
    private $cancelBookingUrl;
    private $bookingDetailsUrl;
    private $bookingDetailsByStoreOrderIdUrl;

    private $clientId;
    private $passKey;
    private $contentType;
    private $collectionLocation;
    private $paymentType;
    private $logger;
    private $headers;

    public function __construct(LoggerInterface $logger)
    {
        $this->baseUrl = getenv('GOSEND_BASE_URL');
        $this->clientId = getenv('GOSEND_CLIENT_ID');
        $this->passKey = getenv('GOSEND_PASS_KEY');
        $this->paymentType = 3;
        $this->contentType = 'application/json';
        $this->collectionLocation = 'pickup';
        $this->logger = $logger;
        $this->headers = ['Client-ID' => $this->clientId, 'Pass-Key' => $this->passKey];

        $this->estimateUrl = '/gokilat/v10/calculate/price';
        $this->createBookingUrl = '/gokilat/v10/booking';
        $this->cancelBookingUrl = '/gokilat/v10/booking/cancel';
        $this->bookingDetailsUrl = '/gokilat/v10/booking/orderno';
        $this->bookingDetailsByStoreOrderIdUrl = '/gokilat/v10/booking/storeOrderId';
    }

    public function estimateCost(array $parameters): array
    {
        $parameters['paymentType'] = $this->paymentType;

        $this->endpoint = sprintf('%s%s?%s', $this->baseUrl,$this->estimateUrl, http_build_query($parameters));

        $result = $this->fetch($parameters);

        $data = [];
        $tempData = [];

        if (count($result) > 0) {

            foreach ($result['data'] as $item) {
                if ($item['serviceable']) {
                    $tempData[] = [
                        'service' => $item['shipment_method'],
                        'description' => $item['shipment_method_description'],
                        'cost' => [
                            [
                                'value' => $item['price']['total_price'],
                                'etd' => $item['shipment_method_description'],
                                'note' => '',
                            ],
                        ],
                    ];
                }
            }

            $data['data'][] = ['costs' => $tempData];
        }

        return $data;
    }

    public function createBooking(array $parameters): array
    {
        $parameters['paymentType'] = $this->paymentType;
        $parameters['collection_location'] = $this->collectionLocation;

        $this->endpoint = sprintf('%s%s', $this->baseUrl,$this->createBookingUrl);

        $response =  $this->fetch($parameters, 'POST');

        return $response;
    }

    public function cancelBooking($orderNo): array
    {
        $this->endpoint = sprintf('%s%s', $this->baseUrl,$this->cancelBookingUrl);

        $parameters = [
            'orderNo' => $orderNo
        ];

        $response = $this->fetch($parameters, 'PUT');

        return $response;
    }

    public function getBookingDetails($orderId): array
    {
        $this->endpoint = sprintf('%s%s/%s', $this->baseUrl, $this->bookingDetailsUrl, $orderId);

        $response = $this->fetch();

        return $response;
    }

    public function getBookingDetailsByStoreOrderId($orderId): array
    {
        $this->endpoint = sprintf('%s%s/%s', $this->baseUrl, $this->bookingDetailsByStoreOrderIdUrl, $orderId);

        $response = $this->fetch();

        return $response;
    }

    private function fetch(array $data = [], string $method = 'GET'): array
    {
        $response = [
            'error' => true,
            'message' => null,
            'data' => null,
        ];

        try {
            if ($method === 'GET') {
                $options = ['headers' => $this->headers];
            }elseif ($method === 'POST' || $method === 'PUT') {
                $options = ['headers' => $this->headers, 'body' => json_encode($data)];
            }

            if (getenv('APP_ENV') === 'dev') {
                // Debug purpose -- fix error cURL error 60: SSL certificate problem in local env
                $options['verify'] = false;
            }

            $result = HttpClientService::run($this->endpoint, $options, $method);

            //--- Debug purpose
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('GoSend API result: %s', json_encode($result)));
            //--- Debug purpose

            if (!$result['error']) {
                $response['error'] = false;
                $response['data'] = $result['data'] ?? [];
            }else {
                $response['message'] = $result['message'];
            }
        } catch (HttpClientException $e) {
            $response['message'] = sprintf('GoSend API exception: %s', $e->getMessage());

            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error($response['message']);
        }

        return $response;
    }

}
