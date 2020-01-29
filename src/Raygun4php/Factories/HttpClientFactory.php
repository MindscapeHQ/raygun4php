<?php

namespace Raygun4php\Factories;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Raygun4php\Factories\Interfaces\HttpClientFactoryInterface;

class HttpClientFactory implements HttpClientFactoryInterface
{
    /**
     * @var string
     */
    private $apiKey;

    private const BASE_URI = 'https://api.raygun.com';
    private const DEFAULT_TIMEOUT = 2.0;

    /**
     * HttpClientFactory
     *
     * Build a Guzzle HTTP client to transmit data with. Base URI set as constant to hide from top-level
     *
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param float|null $timeout
     * @return ClientInterface
     */
    public function build(?float $timeout = self::DEFAULT_TIMEOUT, string $proxy = null): ClientInterface
    {
        return $this->buildInternal($timeout, $proxy);
    }

    /**
     * @param float $timeout
     * @param string|null $proxy
     * @return ClientInterface
     */
    private function buildInternal(?float $timeout = self::DEFAULT_TIMEOUT, string $proxy = null): ClientInterface
    {
        $httpClient = new Client([
            'base_uri' => self::BASE_URI,
            'timeout' => $timeout,
            'headers' => [
                'X-ApiKey' => $this->apiKey
            ]
        ]);

        if (!is_null($proxy)) {
            $httpClient['proxy'] = $proxy;
        }

        return $httpClient;
    }
}
