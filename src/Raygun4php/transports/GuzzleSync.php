<?php

namespace Raygun4php\Transports;

use Raygun4php\Interfaces\RaygunMessageInterface;
use Raygun4php\Interfaces\TransportInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;

class GuzzleSync implements TransportInterface
{
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
    }

    /**
     * Synchronously transmits the message to the raygun API.
     *
     * @param RaygunMessageInterface $message
     * @return boolean False if request fails or of the response status code is not 202.
     */
    public function transmit(RaygunMessageInterface $message): bool
    {
        $messageJson = $message->toJson();

        try {
            $httpResponse = $this->httpClient
                                 ->request('POST', '/entries', ['body' => $messageJson]);
        } catch (TransferException $th) {
            return false;
        }

        $responseCode = $httpResponse->getStatusCode();
        return $responseCode === 202;
    }
}
