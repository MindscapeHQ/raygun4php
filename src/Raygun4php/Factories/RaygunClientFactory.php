<?php

namespace Raygun4php\Factories;

use Psr\Log\LoggerInterface;
use Raygun4php\Factories\Interfaces\RaygunClientFactoryInterface;
use Raygun4php\Interfaces\TransportInterface;
use Raygun4php\RaygunClient;

class RaygunClientFactory implements RaygunClientFactoryInterface
{
    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var bool
     */
    private $useAsync;
    /**
     * @var bool
     */
    private $disableUserTracking;
    private $proxy;
    private $timeout;

    /**
     * RaygunClientFactory
     *
     * Builds a RaygunClient object with the HTTP client and transport built with default configurable values to simplify setup
     *
     * @param string $apiKey
     * @param bool $disableUserTracking
     * @param bool $useAsync
     */
    public function __construct(string $apiKey, bool $disableUserTracking = false, bool $useAsync = true)
    {
        $this->apiKey = $apiKey;
        $this->useAsync = $useAsync;
        $this->disableUserTracking = $disableUserTracking;
    }

    /**
     * @param LoggerInterface $logger
     * @return RaygunClientFactory
     */
    public function setLogger(LoggerInterface $logger): RaygunClientFactory
    {
        $this->logger = $logger;

        return $this;
    }

    public function setProxy(string $proxy): RaygunClientFactory {
        $this->proxy = $proxy;

        return $this;
    }

    public function setTimeout(float $timeout): RaygunClientFactory {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @return RaygunClient
     */
    public function build(): RaygunClient
    {
        $transport = $this->createTransport();

        return new RaygunClient($transport, $this->disableUserTracking);
    }

    /**
     * @return TransportInterface
     */
    private function createTransport(): TransportInterface
    {
        $transportFactory = new TransportFactory($this->apiKey, $this->logger, $this->useAsync);
        $transport = $transportFactory->build($this->timeout, $this->proxy);

        if ($this->useAsync) {
            register_shutdown_function([$transport, 'wait']);
        }

        return $transport;
    }
}
