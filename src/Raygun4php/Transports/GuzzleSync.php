<?php

namespace Raygun4php\Transports;

use Raygun4php\Interfaces\RaygunMessageInterface;
use Raygun4php\Interfaces\TransportInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class GuzzleSync implements TransportInterface, LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @param ClientInterface $httpClient Is expected to have the base_uri option -
     *                                    set and the correct X-ApiKey header set.
     */
    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->logger = new NullLogger();
    }

    /**
     * Synchronously transmits the message to the raygun API.
     *
     * @param RaygunMessageInterface $message
     * @return boolean False if request fails or if the response status code is not 202.
     */
    public function transmit(RaygunMessageInterface $message): bool
    {
        $messageJson = $message->toJson();

        try {
            $httpResponse = $this->httpClient
                                 ->request('POST', '/entries', ['body' => $messageJson]);
        } catch (TransferException $th) {
            $this->logger->error($th->getMessage(), json_decode($messageJson, true));
            return false;
        }

        $responseCode = $httpResponse->getStatusCode();

        if ($responseCode !== 202) {
            $logMsg = "Expected response code 202 but got {$responseCode}";
            if ($responseCode < 400) {
                $this->logger->warning($logMsg, json_decode($messageJson, true));
            } else {
                $this->logger->error($logMsg, json_decode($messageJson, true));
            }
            return false;
        }
        return true;
    }

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
