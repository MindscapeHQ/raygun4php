<?php

namespace Raygun4php\Transports;

use Raygun4php\Interfaces\RaygunMessageInterface;
use Raygun4php\Interfaces\TransportInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise;

class GuzzleAsync implements TransportInterface
{
    /**
     * @var ClientInterface
     */
    private $httpClient;
    private $httpPromises = [];

    /**
     * @param ClientInterface $httpClient
     */
    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function transmit(RaygunMessageInterface $message): bool
    {
        $messageJson = $message->toJson();

        try {
            $this->httpPromises[] = $this->httpClient
                                 ->requestAsync('POST', '/entries', ['body' => $messageJson]);
        } catch (TransferException $th) {
            return false;
        }
        return true;
    }

    public function __destruct()
    {
        Promise\settle($this->httpPromises)->wait(false);
    }
}
