<?php

namespace Raygun4php\Tests\Transports\Batched;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Raygun4php\Transports\Batched\GuzzleBatch;
use Raygun4php\RaygunMessage;
use Psr\Log\Test\TestLogger;

class GuzzleBatchTest extends TestCase
{
    private $testOutputDirectory = "./tests/output/raygun4php/test";

    protected function setUp(): void
    {
        $this->clearTestDirectory();
    }

    public function testEnsureStoreDirectoryExists()
    {
        $transport = $this->getTransport();

        $this->assertTrue(file_exists($this->testOutputDirectory));
    }

    public function testFileIsAddedToStoreOnTransmit()
    {
        $transport = $this->getTransport();

        $message = new RaygunMessage();
        $exception = new Exception("Something went wrong");
        $message->build($exception);
        $transport->transmit($message);

        $this->assertTrue($this->getJsonFileCount() == 1);
    }

    public function testFilesAreClearedWhenBatchIsSent()
    {
        $transport = $this->getTransport(2);
        $logger = new TestLogger();
        $transport->setLogger($logger);

        $message = new RaygunMessage();
        $exception = new Exception("Something went wrong");
        $message->build($exception);
        $transport->transmit($message);

        $this->assertEquals(1, $this->getJsonFileCount());

        $exception = new Exception("Something went wrong again");
        $message->build($exception);
        $transport->transmit($message);
        $transport->wait();

        $this->assertEquals(0, $this->getJsonFileCount());
    }

    private function getTransport(int $maxBatchSize = 10)
    {
        $mock = new MockHandler([
            new Response(202)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        return new GuzzleBatch($client, "test", "./tests/output", $maxBatchSize);
    }

    private function clearTestDirectory()
    {
        if (!file_exists($this->testOutputDirectory)) {
            return;
        }

        array_map("unlink", glob($this->testOutputDirectory . "/*.json"));

        rmdir($this->testOutputDirectory);
    }

    private function getJsonFileCount()
    {
        return count(glob($this->testOutputDirectory . "/*.json"));
    }
}
