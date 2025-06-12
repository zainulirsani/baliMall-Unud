<?php

namespace App\Service;

use App\Exception\HttpClientException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;

class RajaOngkirService
{
    /** @var string $baseUrl */
    private $baseUrl;

    /** @var string $accountType */
    private $accountType;

    /** @var string $apiKey */
    private $apiKey;

    /** @var bool $rebuildCache */
    private $rebuildCache;

    /** @var string $endpoint */
    private $endpoint;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var FilesystemAdapter $cache */
    private $cache;

    public function __construct(array $parameters, LoggerInterface $logger)
    {
        $baseUrl = $parameters['base_url'] ?? 'https://api.rajaongkir.com';
        $accountType = $parameters['account_type'] ?? 'starter';

        if ($accountType === 'pro') {
            $baseUrl = 'https://pro.rajaongkir.com/api';
        }

        $this->baseUrl = $baseUrl;
        $this->accountType = $accountType;
        $this->apiKey = $parameters['api_key'] ?? '';
        $this->rebuildCache = (bool) ($parameters['rebuild_cache'] ?? false);
        $this->logger = $logger;
        $this->cache = new FilesystemAdapter('fs', 0, __DIR__.'/../../var');
    }

    public function getAccountType(): string
    {
        return $this->accountType;
    }

    public function getProvince($province = null): array
    {
        try {
            $cacheKey = 'ro_province_data';
            /** @var CacheItem $provinceData */
            $provinceData = $this->cache->getItem($cacheKey);

            if ($this->rebuildCache) {
                $this->cache->deleteItem($cacheKey);
            }

            if ($provinceData->isHit()) {
                $data = $provinceData->get();
                $exist = array_filter($data, function ($value, $key) use ($province) {
                    if (is_numeric($province) && abs($province) > 0) {
                        return $value['province_id'] === (string) $province;
                    }

                    return $value['province'] === $province;
                },ARRAY_FILTER_USE_BOTH);

                return $exist ? current(array_values($exist)) : $data;
            }

            $this->setEndpoint('province');

            $response = $this->fetch(['id' => '']);

            if (!$response['error']) {
                $provinceData->set($response['data']);
                $this->cache->save($provinceData);

                return $response['data'];
            }
        } catch (InvalidArgumentException $e) {
        }

        return [];
    }

    public function getSubDistrict($cityId = null): array
    {
        try {
            $cacheKey = 'ro_subdistrict_data_city_'.$cityId;
            /** @var CacheItem $subDistrictData */
            $subDistrictData = $this->cache->getItem($cacheKey);

            if ($this->rebuildCache) {
                $this->cache->deleteItem($cacheKey);
            }

            if ($subDistrictData->isHit()) {
                return $subDistrictData->get();
            }

            $this->accountType = 'pro';

            $this->setEndpoint('subdistrict');

            $response = $this->fetch(['city' => $cityId]);

            if (!$response['error']) {
                $subDistrictData->set($response['data']);
                $this->cache->save($subDistrictData);

                return $response['data'];
            }
        } catch (InvalidArgumentException $e) {
        }

        return [];
    }

    public function getProvinceById(string $provinceId): array
    {
        $this->setEndpoint('province');

        $response = $this->fetch(['id' => $provinceId]);

        return !$response['error'] ? $response['data'] : [];
    }

    public function getCity($city = null, $province = null): array
    {
        try {
            $cacheKey = 'ro_city_data';
            /** @var CacheItem $cityData */
            $cityData = $this->cache->getItem($cacheKey);

            if ($this->rebuildCache) {
                $this->cache->deleteItem($cacheKey);
            }

            if ($cityData->isHit()) {
                $data = $cityData->get();
                $exist = array_filter($data, function ($value, $key) use ($city, $province) {
                    if (is_numeric($city) && abs($city) > 0) {
                        if (is_numeric($province) && abs($province) > 0) {
                            return ($value['city_id'] === (string) $city && $value['province_id'] === (string) $province);
                        }

                        return $value['city_id'] === (string) $city;
                    }

                    if (is_string($province) && !empty($province)) {
                        return ($value['city_name'] === $city && $value['province'] === $province);
                    }

                    return $value['city_name'] === $city;
                },ARRAY_FILTER_USE_BOTH);

                return $exist ? current(array_values($exist)) : $data;
            }

            $this->setEndpoint('city');

            $parameters = [
                'id' => '',
                'province' => '',
            ];

            $response = $this->fetch($parameters);

            if (!$response['error']) {
                $cityData->set($response['data']);
                $this->cache->save($cityData);

                return $response['data'];
            }
        } catch (InvalidArgumentException $e) {
        }

        return [];
    }

    public function getCityById(string $cityId, string $provinceId = ''): array
    {
        $this->setEndpoint('city');

        $parameters = [
            'id' => $cityId,
            'province' => $provinceId,
        ];

        $response = $this->fetch($parameters);

        return !$response['error'] ? $response['data'] : [];
    }

    public function getCost(array $data): array
    {
        $this->setEndpoint('cost');

        $parameters = [
            'origin' => $data['origin'] ?? 'n/a', // ID kota atau kabupaten asal
            'destination' => $data['destination'] ?? 'n/a', // ID kota atau kabupaten tujuan
            'weight' => $data['weight'] ?? 1000, // Berat kiriman dalam gram (1000 g -> 1 kg), maks. 30 kg
            'courier' => $data['courier'] ?? 'n/a', // Kode kurir: jne, pos, tiki
        ];

        if ($this->getAccountType() === 'pro') {
            $parameters['originType'] = $data['origin_type'] ?? 'city';
            $parameters['destinationType'] = $data['destination_type'] ?? 'city';
        }

        return $this->fetch($parameters, 'POST');
    }

    private function setEndpoint(string $endpoint): void
    {
        $type = ($this->accountType === 'pro') ? 'api' : $this->accountType;

        $this->endpoint = sprintf('%s/%s/%s', $this->baseUrl, $type, $endpoint);
    }

    private function fetch(array $data = [], string $method = 'GET'): array
    {
        $response = [
            'error' => true,
            'message' => null,
            'data' => null,
        ];

        try {
            $data = array_merge($data, ['key' => $this->apiKey]);
            $key = $method === 'GET' ? 'query' : 'form_params';
            $options = [$key => $data];
            $result = HttpClientService::run($this->endpoint, $options, $method);

            //--- Debug purpose
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('Raja Ongkir API result: %s', json_encode($result)));
            //--- Debug purpose

            if (!$result['error'] && isset($result['data']['rajaongkir'])) {
                $data = $result['data']['rajaongkir'];

                if ((int) $data['status']['code'] === 200) {
                    $response['error'] = false;
                    $response['data'] = $data['results'];
                }
            }
        } catch (HttpClientException $e) {
            $response['message'] = sprintf('Raja Ongkir API exception: %s', $e->getMessage());

            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error($response['message']);
        }

        return $response;
    }

    private function searchProvince($province, array $data)
    {
        if (is_numeric($province) && abs($province) > 0) {
            $columns = array_column($data, 'province_id');
            $index = array_search($province, $columns, false);

            return $data[$index] ?? [];
        }

        if (is_string($province) && !empty($province)) {
            $columns = array_column($data, 'province');
            $index = array_search($province, $columns, false);

            return $data[$index] ?? [];
        }

        return $data;
    }
}
