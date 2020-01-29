<?php

namespace Raygun4php\Factories;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Raygun4php\Factories\Interfaces\TransportFactoryInterface;
use Raygun4php\Interfaces\TransportInterface;
use Raygun4php\Transports\GuzzleAsync;
use Raygun4php\Transports\GuzzleSync;

class TransportFactory implements TransportFactoryInterface
{
    /**
     * @var bool
     */
    private $useAsync;
    /**
     * @var ClientInterface
     */
    private $httpClient;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $apiKey;

    /**
     * TransportFactory
     *
     * Builds a transport interface with the HTTP client (built with default configurable values) passed in
     *
     * @param string $apiKey
     * @param LoggerInterface|null $logger - PSR-3 compatible logger to attach to the transport for debugging
     * @param bool $useAsync - Use asynchronous transmission, defaults to true
     */
    public function __construct(string $apiKey, LoggerInterface $logger = null, bool $useAsync = true)
    {
        $this->apiKey = $apiKey;
        $this->useAsync = $useAsync;
        $this->httpClient = new HttpClientFactory($apiKey);
        $this->logger = $logger;
    }

    /**
     * @return TransportInterface
     */
    public function build(): TransportInterface
    {
        $httpClientFactory = new HttpClientFactory($this->apiKey);
        $httpClient = $httpClientFactory->build();

        $transport = new GuzzleAsync($httpClient);

        if (!$this->useAsync) {
            $transport = new GuzzleSync($httpClient);
        }

        if (!is_null($this->logger)) {
            $transport->setLogger($this->logger);
        }

        return $transport;
    }
}
