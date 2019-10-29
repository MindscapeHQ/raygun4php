<?php

namespace Raygun4php\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Raygun4php\RaygunClient;
use Raygun4php\RaygunMessage;
use Raygun4php\RaygunRequestMessage;
use Raygun4php\Interfaces\TransportInterface;

class RaygunClientTest extends TestCase
{
    /**
     * @var RaygunClient
     */
    protected $client;

    /**
     * @var TransportInterface&MockObject
     */
    protected $transportMock;

    protected function setUp()
    {
        $this->transportMock = $this->getMockBuilder(TransportInterface::class)
                                    ->setMethods(['transmit'])
                                    ->getMock();


        $this->client = new RaygunClient($this->transportMock);
    }
    

    /**
     * @expectedException \Raygun4php\Raygun4PhpException
     * @expectedExceptionMessage API not valid, cannot send message to Raygun
     */
    public function testSendReturns403WithInvalidApiKey()
    {
        $this->client->SendException(new \Exception(''));
    }

    public function testGetFilteredParamsRemovesByKey()
    {
        $this->client->setFilterParams(array(
            'MyParam' => true
        ));
        $message = $this->getEmptyMessage();
        $message->Details->Request->Form = array(
            'MyParam' => 'secret',
        );
        $message->Details->Request->Headers = array(
            'MyParam' => 'secret',
        );
        $message->Details->Request->Data = array(
            'MyParam' => 'secret',
        );

        $filteredMessage = $this->client->filterParamsFromMessage($message);
        $this->assertEquals(
            $filteredMessage->Details->Request->Form,
            array('MyParam' => '[filtered]',)
        );
        $this->assertEquals(
            $filteredMessage->Details->Request->Headers,
            array('MyParam' => '[filtered]',)
        );
        $this->assertEquals(
            $filteredMessage->Details->Request->Data,
            array('MyParam' => '[filtered]',)
        );
    }

    public function testGetFilteredParamsIgnoresCase()
    {
        $this->client->setFilterParams(array('myparam' => true));
        $message = $this->getEmptyMessage();
        $message->Details->Request->Form = array('MyParam' => 'secret',);

        $filteredMessage = $this->client->filterParamsFromMessage($message);
        $this->assertEquals(
            $filteredMessage->Details->Request->Form,
            array('MyParam' => '[filtered]',)
        );
    }

    public function testGetFilteredParamsAcceptsCustomFunctions()
    {
        $this->client->setFilterParams(array(
            'MyParam' => function ($key, $val) {
                return strrev($val);
            },
        ));
        $message = $this->getEmptyMessage();
        $message->Details->Request->Form = array(
            'MyParam' => 'secret',
        );

        $filteredMessage = $this->client->filterParamsFromMessage($message);
        $this->assertEquals(
            $filteredMessage->Details->Request->Form,
            array(
                'MyParam' => 'terces',
            )
        );
    }

    public function testGetFilteredParamsRemovesRawData()
    {
        $message = $this->getEmptyMessage();
        $message->Details->Request->RawData = 'some-data';

        $filteredMessage = $this->client->filterParamsFromMessage($message);
        $this->assertEquals($filteredMessage->Details->Request->RawData, 'some-data');

        $this->client->setFilterParams(array('MySensitiveParam' => true));
        $filteredMessage = $this->client->filterParamsFromMessage($message);
        $this->assertNull($filteredMessage->Details->Request->RawData);
    }

    public function testGetFilteredParamsParsesRegex()
    {
        $this->client->setFilterParams(array('/MyRegex.*/' => true,));
        $message = $this->getEmptyMessage();
        $message->Details->Request->Form = array(
            'MyParam' => 'some val',
            'MyRegexParam' => 'secret',
        );

        $filteredMessage = $this->client->filterParamsFromMessage($message);
        $this->assertEquals(
            $filteredMessage->Details->Request->Form,
            array(
                'MyParam' => 'some val',
                'MyRegexParam' => '[filtered]',
            )
        );
    }

    protected function getEmptyMessage()
    {
        $requestMessage = new RaygunRequestMessage();
        $requestMessage->HostName = null;
        $requestMessage->Url = null;
        $requestMessage->HttpMethod = null;
        $requestMessage->IpAddress = null;
        $requestMessage->QueryString = null;
        $requestMessage->Headers = null;
        $requestMessage->Data = null;
        $requestMessage->RawData = null;
        $requestMessage->Form = null;
        $message = new RaygunMessage(0);
        $message->Details->Request = $requestMessage;

        return $message;
    }

    public function testFilterParamsFromMessage()
    {
        /** @var RaygunMessage&MockObject $message */
        $message = $this->getMockBuilder(RaygunMessage::class)->getMock();

        $this->assertSame(
            $message,
            $this->client->filterParamsFromMessage($message)
        );
    }

    public function testCanSetAndFilterParams()
    {
        $params = array(
            'bar' => 'baz',
        );

        $this->client->setFilterParams($params);

        $this->assertSame(
            $params,
            $this->client->getFilterParams()
        );
    }

    public function testCanSetAndGetProxy()
    {
        $proxy = 'bar';

        $this->client->setProxy($proxy);

        $this->assertSame(
            $proxy,
            $this->client->getProxy()
        );
    }

    public function testSendExceptionInvokesTransportTransmit()
    {
        $this->transportMock->expects($this->once())
                            ->method('transmit');
        $this->client->SendException(new \Exception('test'));
    }

    public function testSendErrorInvokesTransportTransmit()
    {
        $this->transportMock->expects($this->once())
                            ->method('transmit');
        $this->client->SendError(0, 'Test', __FILE__, __LINE__);
    }
}
