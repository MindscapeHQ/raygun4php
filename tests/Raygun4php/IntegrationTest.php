<?php

namespace Raygun4php;

use PHPUnit_Framework_TestCase;

class IntegrationTest extends PHPUnit_Framework_TestCase
{
    protected $client = null;

    public function setUp()
    {
        if (isset($_SERVER['RAYGUN_API_KEY'])) {
            $this->client = new RaygunClient($_SERVER['RAYGUN_API_KEY'], true);

            $this->client->setFilterParams(array(
                'RAYGUN_API_KEY' => false
            ));
        }
    }

    public function testSendingException()
    {
        if (!$this->client) {
            $this->markTestSkipped('Cannot perform integration test: no RAYGUN_API_KEY environment variable');
            return;
        }

        $this->client->sendException(new \Exception('Just a simple test error'));
    }

    public function testSendingExceptionWithInnerException()
    {
        if (!$this->client) {
            $this->markTestSkipped('Cannot perform integration test: no RAYGUN_API_KEY environment variable');
            return;
        }

        $this->client->sendException(
            new \Exception('Just a slightly more complicated test exception', 1337, new \Exception('Just an inner test exception')),
            array('some-tag'),
            array('foo' => 'bar'),
            time() - 10
        );
    }
}
