<?php

namespace Raygun4php\Transports\Batched;

use Raygun4php\Interfaces\RaygunMessageInterface;
use Raygun4php\Interfaces\TransportInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Raygun4php\Transports\Batched\Interfaces\BatcherInterface;

class GuzzleBatch implements TransportInterface, LoggerAwareInterface
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
     * @var BatcherInterface
     */
    private $batcher;

    /**
     * @var int
     */
    private $maxBatchSize;

    /**
     * @param ClientInterface $httpClient Is expected to have the base_uri option -
     *                                    set and the correct X-ApiKey header set.
     * @param string $appIdentifier - a static string to identify a particular application, e.g. a hash of the API key
     * @param string $storeDirectoryPath - a directory (e.g. /tmp) where files can be safely stored on disk
     * @param int $maxBatchSize - Maximum number of files which will be stored on disk, defaults to 100
     */
    public function __construct(
        ClientInterface $httpClient,
        string $appIdentifier,
        string $storeDirectoryPath,
        int $maxBatchSize = 100
    ) {
        $this->httpClient = $httpClient;
        $this->logger = new NullLogger();
        $this->maxBatchSize = $maxBatchSize;
        $this->batcher = new DiskBatcher($appIdentifier, $storeDirectoryPath);

        register_shutdown_function([$this, "sendBatch"]);
    }

    /**
     * Asynchronously transmits the message to the raygun API.
     * Relies on the wait() method of this object being called to settle any pending requests.
     *
     * @param RaygunMessageInterface $message
     * @return boolean
     */
    public function transmit(RaygunMessageInterface $message): bool
    {
        $messageJson = $message->toJson();

        $this->batcher->add($messageJson);

        return $this->sendBatch();
    }

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        $this->batcher->setLogger($logger);
    }

    /**
     * Calling this method will settle any pending request.
     * Calling this method should typically handled by a function registered as a shutdown function.
     * @see https://www.php.net/manual/en/function.register-shutdown-function.php
     *
     * @return void
     */
    public function wait(): void
    {
        Promise\settle($this->httpPromises)->wait(false);
    }

    public function sendBatch(): bool
    {
        if ($this->batcher->count() >= $this->maxBatchSize) {
            $batch = $this->batcher->build();
            return $this->sendToApi($batch);
        }
        return false;
    }

    private function sendToApi(string $batch)
    {
        try {
            $this->httpPromises[] = $this->httpClient
                                ->requestAsync('POST', '/entries/bulk', ['body' => $batch])
                                ->then(
                                    function (ResponseInterface $response) use ($batch) {
                                        $responseCode = $response->getStatusCode();
                                        if ($responseCode !== 202) {
                                            $logMsg = "Expected response code 202 but got {$responseCode}";
                                            if ($responseCode < 400) {
                                                $this->logger->warning($logMsg, json_decode($batch, true));
                                            } else {
                                                $this->logger->error($logMsg, json_decode($batch, true));
                                            }
                                        }
                                    },
                                    function (RequestException $exception) use ($batch) {
                                        $this->logger->error($exception->getMessage(), json_decode($batch, true));
                                    }
                                )
                                 ;
        } catch (TransferException $th) {
            return false;
        }
        return true;
    }
}
