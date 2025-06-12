<?php

namespace App\Service;

use App\Exception\HttpClientException;
use Exception;
use GuzzleHttp\Client;

class HttpClientService
{
    /**
     * @param string $url
     * @param array  $options
     * @param string $method
     *
     * @return array
     * @throws HttpClientException
     */
    public static function run(string $url, array $options = [], string $method = 'GET'): array
    {
        $reserved = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        if (!in_array(strtoupper($method), $reserved, false)) {
            throw new HttpClientException(sprintf('HttpClient only accept these methods: %s', implode(', ', $reserved)));
        }

        $result = [
            'error' => true,
            'message' => null,
            'data' => null,
        ];

        try {
            $client = new Client();
            $response = $client->request($method, $url, $options);

            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                $result['error'] = false;
                $result['data'] = json_decode($response->getBody(), true);
            }
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }
}
