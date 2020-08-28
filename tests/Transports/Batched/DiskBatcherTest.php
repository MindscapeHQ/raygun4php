<?php

namespace Raygun4php\Tests\Transports\Batched;

use PHPUnit\Framework\TestCase;
use Raygun4php\Transports\Batched\DiskBatcher;

class DiskBatcherTest extends TestCase
{
    private $testOutputDirectory = "./tests/batcher-output";
    private $testAppIdentifier = "abc123";

    protected function setUp()
    {
        $this->clearTestDirectory();
    }

    public function testOutputDirectoryIsCreated()
    {
        new DiskBatcher($this->testAppIdentifier, $this->testOutputDirectory);

        $this->assertTrue(file_exists("{$this->testOutputDirectory}/raygun4php/{$this->testAppIdentifier}"));
    }

    public function testAddMatchesContentsOfMessage()
    {
        $batcher = new DiskBatcher($this->testAppIdentifier, $this->testOutputDirectory);

        $messageJson = "{ \"test\": 123 }";

        $batcher->add($messageJson);

        $outputFiles = glob("{$this->testOutputDirectory}/raygun4php/{$this->testAppIdentifier}/*.json");

        $this->assertTrue(count($outputFiles) == 1);

        $content = file_get_contents($outputFiles[0]);

        $this->assertTrue($content == $messageJson);
    }

    public function testBuildCreatesValidJsonArray()
    {
        $batcher = new DiskBatcher($this->testAppIdentifier, $this->testOutputDirectory);

        $batcher->add("{\"test\":123}");
        $batcher->add("{\"test\":456}");

        $batch = $batcher->build();

        $jsonArr = json_decode($batch);

        $this->assertEquals(2, count($jsonArr));
    }

    public function testCountMatchesPayloadsAdded()
    {
        $batcher = new DiskBatcher($this->testAppIdentifier, $this->testOutputDirectory);

        $batcher->add("{\"test\":1}");
        $batcher->add("{\"test\":2}");
        $batcher->add("{\"test\":3}");

        $count = $batcher->count();

        $this->assertEquals(3, $count);
    }

    public function testCountReturnsToZeroOnBuild()
    {
        $batcher = new DiskBatcher($this->testAppIdentifier, $this->testOutputDirectory);

        $batcher->add("{\"test\":1}");
        $batcher->add("{\"test\":2}");

        $this->assertEquals(2, $batcher->count());
        $batcher->build();

        $this->assertEquals(0, $batcher->count());
    }

    private function clearTestDirectory()
    {
        $outputDir = "{$this->testOutputDirectory}/raygun4php/{$this->testAppIdentifier}";
        if (!file_exists($outputDir)) {
            return;
        }

        array_map("unlink", glob($outputDir . "/*.json"));

        rmdir($outputDir);
    }
}
