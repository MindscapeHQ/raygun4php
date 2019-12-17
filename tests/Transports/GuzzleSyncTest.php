<?php

namespace Raygun4php\Tests\Transports;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\Test\TestLogger;
use Raygun4php\RaygunMessage;
use Raygun4php\Transports\GuzzleSync;

class GuzzleSyncTest extends TestCase
{
    public function testTransmitReturnsTrueIfResponseCodeIs202()
    {
        $mock = new MockHandler([
            new Response(202)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $transport = new GuzzleSync($client);
        $message = new RaygunMessage();

        $this->assertTrue($transport->transmit($message));
    }

    public function testTransmitReturnsFalseIfResponseIs400()
    {
        $mock = new MockHandler([
            new Response(400)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $transport = new GuzzleSync($client);
        $message = new RaygunMessage();

        $this->assertFalse($transport->transmit($message));
    }

    public function testTransmitReturnsFalsIfHttpClientThrowsException()
    {
        $mock = new MockHandler([
            new ConnectException('Connection failed', new Request('POST', 'test'))
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $transport = new GuzzleSync($client);
        $message = new RaygunMessage();

        $this->assertFalse($transport->transmit($message));
    }

    public function testTransmitLogsErrorIfHttpClientThrowsException()
    {
        $mock = new MockHandler([
            new ConnectException('Connection failed', new Request('POST', 'test'))
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $transport = new GuzzleSync($client);
        $message = new RaygunMessage();

        $logger = new TestLogger();
        $transport->setLogger($logger);

        $transport->transmit($message);

        $this->assertTrue($logger->hasErrorRecords());
    }

    public function testTransmitLogsRelevant400Message()
    {
        $mock = new MockHandler([
            new Response(400)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.raygun.io']);

        $transport = new GuzzleSync($client);
        $message = new RaygunMessage();

        $logger = new TestLogger();
        $transport->setLogger($logger);

        $transport->transmit($message);

        $this->assertTrue($logger->hasErrorThatContains('400'));
    }

    public function testTransmitLogsRelevant400MessageNoException()
    {
        $mock = new MockHandler([
            new Response(400)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler,
                              'base_uri' => 'https://api.raygun.io',
                              'http_error' => false
                              ]);

        $transport = new GuzzleSync($client);
        $message = new RaygunMessage();

        $logger = new TestLogger();
        $transport->setLogger($logger);

        $transport->transmit($message);

        $this->assertTrue($logger->hasErrorThatContains('400'));
    }

    public function testTransmitLogsRelevant403Message()
    {
        $mock = new MockHandler([
            new Response(403)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.raygun.io']);

        $transport = new GuzzleSync($client);
        $message = new RaygunMessage();

        $logger = new TestLogger();
        $transport->setLogger($logger);

        $transport->transmit($message);

        $this->assertTrue($logger->hasErrorThatContains('403'));
    }

    public function testTransmitReturnsFalseOnHttpStatus400NoException()
    {
        $mock = new MockHandler([
            new Response(400)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'http_errors' => false]);

        $transport = new GuzzleSync($client);
        $message = new RaygunMessage();

        $this->assertFalse($transport->transmit($message));
    }
}
