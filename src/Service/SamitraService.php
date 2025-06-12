<?php

namespace App\Service;

use App\Exception\HttpClientException;
use Psr\Log\LoggerInterface;

class SamitraService
{
    /** @var string $baseUrl */
    private $baseUrl;

    /** @var string $endpoint */
    private $endpoint;

    /** @var LoggerInterface $logger */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->baseUrl = getenv('SAMITRA_BASE_URL');
        $this->logger = $logger;
    }

    public function getCost(array $data): array
    {
        $parameters = [
            'alamat1' => $data['origin'] ?? '', // Nama kota atau kabupaten pengiriman
            'alamat2' => $data['destination'] ?? '', // Nama kota atau kabupaten tujuan
            'berat' => $data['weight'] ?? 1, // Berat kiriman dalam kilogram
            'pengemasan' => $data['packaging'] ?? 0, // Pengemasan -> nilai lain harus cek API pengemasan
            'extra_tarif' => $data['extra_tariff'] ?? 1, // Enable/disable extra tarif
        ];

        $this->endpoint = sprintf('%s/api/cek_tarif/?%s', $this->baseUrl, http_build_query($parameters));

        return $this->fetch();
    }

    private function fetch(array $data = [], string $method = 'GET'): array
    {
        $response = [
            'error' => true,
            'message' => null,
            'data' => null,
        ];

        try {
            $key = $method === 'GET' ? 'query' : 'form_params';
            $options = [$key => $data];

            if (getenv('APP_ENV') === 'dev') {
                // Debug purpose -- fix error cURL error 60: SSL certificate problem in local env
                $options['verify'] = false;
            }

            $result = HttpClientService::run($this->endpoint, $options, $method);

            //--- Debug purpose
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('SAMITRA API result: %s', json_encode($result)));
            //--- Debug purpose

            if (!$result['error']) {
                $results = $result['data']['result'] ?? [];

                if (isset($results['status']) && (int) $results['status'] === 200) {
                    $data = [];

                    // Process data to match existing output,
                    // so that the JS function that handle this output does not need to be changed
                    foreach ($results['data'] as $item) {
                        $data[] = [
                            'service' => $item['id_tarif_layanan'],
                            'description' => $item['nama_layanan'],
                            'cost' => [
                                [
                                    'value' => $item['harga'],
                                    'etd' => $item['estimasi'],
                                    'note' => '',
                                ],
                            ],
                        ];
                    }

                    $response['error'] = false;
                    $response['data'][] = ['costs' => $data];
                }
            }
        } catch (HttpClientException $e) {
            $response['message'] = sprintf('SAMITRA API exception: %s', $e->getMessage());

            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error($response['message']);
        }

        return $response;
    }
}
