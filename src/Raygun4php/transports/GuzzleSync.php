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
            $httpResponse = $this->httpClient
                                 ->request('POST', '/entries', ['body' => $messageJson]);
        } catch (TransferException $th) {
            return false;
        }

        $responseCode = $httpResponse->getStatusCode();

        return $responseCode === 202;
    }
}
