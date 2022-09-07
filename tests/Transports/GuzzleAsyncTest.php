<?php

namespace Raygun4php\Tests\Transports;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Raygun4php\RaygunMessage;
use Raygun4php\Transports\GuzzleAsync;

class GuzzleAsyncTest extends TestCase
{

    public function testTransmitLogsErrorIfResponseCodeIs400()
    {
        $mock = new MockHandler([
            new Response(400)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $transport = new GuzzleAsync($client);
        $message = new RaygunMessage();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())->method('error');
        $transport->setLogger($logger);

        $transport->transmit($message);
        $transport->wait();
    }

    public function testTransmitLogsWarningIfResponseCodeIs200()
    {
        $mock = new MockHandler([
            new Response(200)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $transport = new GuzzleAsync($client);
        $message = new RaygunMessage();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())->method('warning');
        $transport->setLogger($logger);

        $transport->transmit($message);
        $transport->wait();
    }

    public function testTransmitLogsErrorIfHttpClientThrowsException()
    {
        $mock = new MockHandler([
            new RequestException('Connection failed', new Request('POST', 'test'))
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $transport = new GuzzleAsync($client);

        $message = new RaygunMessage();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())->method('error');
        $transport->setLogger($logger);

        $transport->transmit($message);
        $transport->wait();
    }
}
