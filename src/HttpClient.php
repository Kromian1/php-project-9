<?php

namespace Analyzer;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class HttpClient
{
    private Client $client;

    public function __construct(int $timeout = 10, int $connectTimeout = 5)
    {
        $this->client = new Client([
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout
        ]);
    }

    public function fetch(string $method, string $url): array
    {
        try {
            $response = $this->client->request($method, $url);
            return [
                'statusCode' => $response->getStatusCode(),
                'body' => (string) $response->getBody(),
                'result' => 'success'
            ];
        } catch (ConnectException $e) {
            return [
                'statusCode' => 500
            ];
        } catch (RequestException $e) {
            return [
                'statusCode' => $e->getResponse()->getStatusCode()
            ];
        }
    }
}
