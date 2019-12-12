<?php

namespace Raygun4php\Transports;

use Raygun4php\Interfaces\RaygunMessageInterface;
use Raygun4php\Interfaces\TransportInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class GuzzleAsync implements TransportInterface, LoggerAwareInterface
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
     * @var Promise[]
     */
    private $httpPromises = [];

    /**
     * @param ClientInterface $httpClient Is expected to have the base_uri option -
     *                                    set and the correct X-ApiKey header set.
     */
    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Asynchronously transmits the message to the raygun API.
     * Relies on the destructor of this object to settle any pending requests.
     *
     * @param RaygunMessageInterface $message
     * @return boolean
     */
    public function transmit(RaygunMessageInterface $message): bool
    {
        $messageJson = $message->toJson();

        try {
            $this->httpPromises[] = $this->httpClient
                                ->requestAsync('POST', '/entries', ['body' => $messageJson])
                                ->then(
                                    function (ResponseInterface $response) use ($messageJson) {
                                        $responseCode = $response->getStatusCode();
                                        if ($responseCode !== 202) {
                                            $logMsg = "Expected response code 202 but got {$responseCode}";
                                            if ($responseCode < 400) {
                                                $this->logger->warning($logMsg, json_decode($messageJson, true));
                                            } else {
                                                $this->logger->error($logMsg, json_decode($messageJson, true));
                                            }
                                        }
                                    },
                                    function (RequestException $exception) use ($messageJson) {
                                        $this->logger->error($exception->getMessage(), json_decode($messageJson, true));
                                    }
                                )
                                 ;
        } catch (TransferException $th) {
            return false;
        }
        return true;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __destruct()
    {
        Promise\settle($this->httpPromises)->wait();
    }
}