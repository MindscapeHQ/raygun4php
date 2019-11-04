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
