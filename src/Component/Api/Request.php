<?php

namespace App\Component\Api;

use GuzzleHttp\Client;

class Request
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $config = ['http_errors' => false, 'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json']];

    /**
     * Request constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param string $url
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function GET(string $url): Response
    {
        $response = $this->client->request('GET', $url);

        return new Response(
            $response->getStatusCode(),
            $response->getHeaders()
        );
    }

    /**
     * @param string $url
     * @param array $postData
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function POST(string $url, $postData = []): Response
    {
        $response = $this->client->request('POST', $url, array_merge($this->config, ['body' => $postData]));

        return new Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody()
        );
    }
}
