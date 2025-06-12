<?php

namespace App\Service;

use App\Exception\HttpClientException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TokoDaringService
{
    protected $headers;
    protected $categoryList = [
        67 => [
            'id' => 1,
            'name' => 'Makanan'
        ],
        138 => [
            'id' => 2,
            'name' => 'Transportasi'
        ],
        159 => [
            'id' => 3,
            'name' => 'Kurir'
        ],
        25 => [
            'id' => 4,
            'name' => 'Furnitur'
        ],
        29 => [
            'id' => 5,
            'name' => 'ATK'
        ],
        66 => [
            'id' => 6,
            'name' => 'Suvenir'
        ],
        27 => [
            'id' => 7,
            'name' => 'Alat Kesehatan'
        ],
        26 => [
            'id' => 8,
            'name' => 'Fashion'
        ],
        13 => [
            'id' => 9,
            'name' => 'Perkakas'
        ],
        84 => [
            'id' => 10,
            'name' => 'Jasa Kreatif'
        ],
        123 => [
            'id' => 11,
            'name' => 'Akomodasi'
        ],
        7 => [
            'id' => 12,
            'name' => 'Elektronik'
        ],
        160 => [
            'id' => 13,
            'name' => 'Sewa Peralatan'
        ]

    ];
    protected $logger;
    protected $endpoint;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->headers = [
            "X-Client-Id" => getenv('TOKO_DARING_X_CLIENT_ID'),
            "X-Client-Secret" => getenv('TOKO_DARING_X_CLIENT_SECRET'),
            'Content-Type' => 'application/json'
        ];
    }

    public function getTokoDaringCategoryId($categoryId):string
    {
        return $this->categoryList[$categoryId]['id'];
    }

    public function sendReportTransactionToTokoDaring($data){
        $this->endpoint = getenv('TOKO_DARING_REPORT_URL');

        if (count($data['orders']) > 0) {

            $orders = $data['orders'];
            $pcId = $data['pc_id'];
            $orderDesc = '';
            $orderId = $orders[0]->getSharedInvoice();
            $buyer = $orders[0]->getBuyer();
            $sellers = '';
            $paymentMethod = 'transfer';
            $valuasi = 0;

            try {
                $categoryId = $this->getTokoDaringCategoryId($pcId);
            }catch (\Throwable $throwable) {
                $this->logger->error(sprintf('Error converting category id %s', $throwable->getMessage()));
                throw new NotFoundHttpException('Error converting category id');
            }

            foreach ($orders as $order) {
                $buyer = $order->getBuyer();
                $seller = $order->getSeller();
                $products = $order->getOrderProducts();
                $ppn = 0;

                foreach ($products as $product) {
                    $orderDesc .= $product->getOriginalName();

                    if (count($products) > 1 || count($orders) > 1) {
                        $orderDesc .= ', ';
                    }

//                    if ($product->getWithTax() && $product->getTaxNominal() > 0) {
//                        $ppn += $product->getTaxNominal();
//                    }
                }

                $sellers .= $seller->getName();

                if (count($orders) > 1) {
                    $sellers .= ', ';
                }

                $valuasi += ($order->getTotal() + $order->getShippingPrice());
                $valuasi += $ppn;
            }

            $requestBody = [
                'valuasi' => (int) $valuasi,
                'id_kategori' => $categoryId,
                'order_id' => $orderId,
                'order_desc' => $orderDesc,
                'email' => $buyer->getEmail(),
                'phone' => $buyer->getPhoneNumber(),
                'username' => $buyer->getUsername(),
                'nama_merchant' => $sellers,
                'metode_bayar' => $paymentMethod,
                'token' => $buyer->getLkppJwtToken()
            ];

            return $this->send($requestBody);
        }
    }

    public function sendConfirmationTransactionToTokoDaring($order, $status) {
        $this->endpoint = getenv('TOKO_DARING_CONFIRMATION_REPORT_URL');

        $payload = [
            'order_id' => $order->getSharedInvoice(),
            'konfirmasi_ppmse' => $status === 'paid',
            'keterangan_ppmse' => 'Konfirmasi transaksi BaliMall'
        ];

        return $this->send($payload);
    }

    private function send(array $data = [], string $method = 'POST'): array
    {
        $response = [
            'error' => true,
            'message' => null,
            'data' => null,
        ];

        try {
            $options = ['headers' => $this->headers, 'json' => $data];

            //--- Debug purpose
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('TOKODARING API request: %s', json_encode($options)));
            //--- Debug purpose

            if (getenv('APP_ENV') === 'dev') {
                // Debug purpose -- fix error cURL error 60: SSL certificate problem in local env
                $options['verify'] = false;
            }

            $result = HttpClientService::run($this->endpoint, $options, $method);

            //--- Debug purpose
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('TOKODARING API result: %s', json_encode($result)));
            //--- Debug purpose

            if (!$result['error']) {
                $response['error'] = false;
                $response['data'] = $result['data'] ?? [];
            }else {
                $response['message'] = $result['message'];
            }
        } catch (HttpClientException $e) {
            $response['message'] = sprintf('TOKODARING API exception: %s', $e->getMessage());

            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error($response['message']);
        }

        return $response;
    }
}
