<?php


namespace App\Service;


use App\Exception\HttpClientException;
use App\Helper\StaticHelper;
use Psr\Log\LoggerInterface;

class DJPService
{

    protected $headers;
    protected $endpoint;
    protected $logger;
    protected $baseUrl;

    protected $postTokenUrl;
    protected $transactionUrl;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->baseUrl = getenv('DJP_BASE_URL');

        $this->postTokenUrl = $this->baseUrl . '/v1/token';
        $this->transactionUrl = $this->baseUrl . '/v1/transactions/';
    }

    private function getToken()
    {
        /**
         * @TODO save token di cache/db ?
         */

        $token = null;

        $username = getenv('DJP_USERNAME');
        $password = getenv('DJP_PASSWORD');

        $encodedCredential = base64_encode(sprintf('%s:%s', $username, $password));

        $payload = [
            "grant_type" => "client_credentials"
        ];

        $this->headers = [
            'Authorization' => 'Basic '.$encodedCredential
        ];

        $this->endpoint = $this->postTokenUrl;

        $result = $this->send('form_params', $payload);

        if ($result['error'] === false) {
            $data = $result['data'];

            $token = $data['access_token'];
            $expires = $data['expires_in'];
        }

        return $token;
    }

    public function getTransactions($orderId)
    {
        $token = $this->getToken();

        $this->headers = [
            'Authorization' => 'Bearer '.$token
        ];

        $this->endpoint = $this->transactionUrl . $orderId;

        return $this->send('json', [], 'GET');
    }

    public function postTransations($order, $status='new')
    {
        $token = $this->getToken();
        $today = (new \DateTime())->format('Y-m-d');

        $this->headers = [
            'Authorization' => 'Bearer '.$token
        ];

        $this->transactionUrl .= sha1($order->getInvoice());
        $this->endpoint = $this->transactionUrl;

        $buyer = $order->getBuyer();
        $seller = $order->getSeller();

        $payload = [];
        $payload['requestId'] = sha1($order->getInvoice());
        $payload['noDok'] = $order->getInvoice();
        $payload['tglDok'] = $today;
        $payload['status'] = $status;
        $buyer_npwp = !empty($order->getTaxDocumentNpwp()) && $order->getTaxDocumentNpwp() != null ? preg_replace('/[^0-9]/', '', $order->getTaxDocumentNpwp()) : preg_replace('/[^0-9]/', '', $buyer->getNpwp());
        $payload['pihak'] = [
            [
                'jenisId' => 'npwp',
                'nomorId' => preg_replace('/[^0-9]/', '', $seller->getUser()->getNpwp()),
                'nama' => $seller->getName(),
                'sebagai' => 'penjual',
            ],
            [
                'jenisId' => 'npwp',
                'nomorId' => $buyer_npwp,
                // 'nama' => sprintf('%s %s', $buyer->getFirstName(), $buyer->getLastName()),
                'nama' => $order->getWorkUnitName(),
                'sebagai' => 'pembeli',
            ]
        ];

        $products = [];

        $shipping_price = 0;
        foreach ($order->getOrderProducts() as $orderProduct) {
            $products[] = [
                'sku' => '',
                'keterangan' => $orderProduct->getOriginalName(),
                'hargaPerUnit' => (float) $orderProduct->getPrice(),
                'jumlahUnit' => (int) $orderProduct->getQuantity(),
                'hargaTotal' => (float) $orderProduct->getTotalPrice(),
            ];
            if (intval($order->getShippingPrice()) != 0) {
                $shipping_price = floatval($orderProduct->getPriceShippingNegotiation());
            }
        }
        if (intval($order->getShippingPrice()) != 0) {
            $products[] = [
                'keterangan' => 'Biaya Pengiriman oleh Kurir Toko',
                'hargaPerUnit' => $shipping_price,
                'jumlahUnit' => '1',
                'hargaTotal' => $shipping_price,
            ];
        }


        $payload['barangDanJasa'] = $products;

        $ppn = 11/100;
        $pph = 0.5/100;

        $dpp = 0;
        $dpp_pph = 0;
        $ppn_product = 0;
        $with_tax = false;
        $tax_value = 0;
        foreach ($order->getOrderProducts() as $orderProduct) {
            if ($orderProduct->getWithTax() == true) {
                $with_tax = $orderProduct->getWithTax();
                $tax_value = $orderProduct->getTaxValue();
            }

            if ($orderProduct->getWithTax() == true) {
                $dpp = $dpp + (floatval($orderProduct->getPrice()) * floatval($orderProduct->getQuantity()));
            }

            $dpp_pph = $dpp_pph + (floatval($orderProduct->getPrice())  * floatval($orderProduct->getQuantity()));
            $ppn_product = $ppn_product + $orderProduct->getTaxNominal();
        }

        $ppn_shipping = $shipping_price * $ppn;
        $dpp = $dpp + $shipping_price;
        $dpp_pph = $dpp_pph + $shipping_price;
        $ppn_product = $ppn_product + $ppn_shipping;
        $payload['objekPajak'] = [
            [
                'kode' => 'ppn',
                'dpp' => $dpp,
                'tarif' => 11,
                'pajak' => $ppn_product,
                'tglPemotongan' => $today
            ],
            [
                'kode' => '22-101-01',
                'dpp' => $dpp_pph,
                'tarif' => 0.5,
                'pajak' => ($dpp_pph * $pph),
                'tglPemotongan' => $today
            ]
        ];

        $this->logger->debug('DJP Post transaction payload', $payload);

        return $this->send('json', $payload);
    }


    private function send(string $type, array $data = [],  string $method = 'POST'): array
    {
        $response = [
            'error' => true,
            'message' => null,
            'data' => null,
        ];

        try {
            $options = ['headers' => $this->headers, $type => $data];

            //--- Debug purpose
            $this->logger->error('DJP API Endpoint', [$this->endpoint]);
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('DJP API request: %s', json_encode($options)));
            //--- Debug purpose

            if (getenv('APP_ENV') === 'dev') {
                // Debug purpose -- fix error cURL error 60: SSL certificate problem in local env
                $options['verify'] = false;
            }

            $options['verify'] = false;

            $result = HttpClientService::run($this->endpoint, $options, $method);

            //--- Debug purpose
            $this->logger->error(sprintf('DJP API Result: %s', json_encode($result)));
            //--- Debug purpose

            if (!$result['error']) {
		if (
                    (isset($result['data']['access_token']) && !empty($result['data']['access_token'])) ||
                    (isset($result['data']['status']) && $result['data']['status'] === 'success')
                ) {
                    $response['error'] = false;
                    $response['data'] = $result['data'] ?? [];

                    $this->logger->error('DJP Report Success', [$response['data']]);
                }else {
                    $this->logger->error('DJP Report Payload Error', [$response['data']]);
                }
            }else {
                $response['message'] = $result['message'];
            }
        } catch (HttpClientException $e) {
            $response['message'] = sprintf('DJP API exception: %s', $e->getMessage());

            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error($response['message']);
        }

        return $response;
    }
}
