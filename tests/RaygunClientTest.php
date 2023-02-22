<?php

namespace Raygun4php\Tests;

use JsonSchema\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Raygun4php\RaygunClient;
use Raygun4php\RaygunMessage;
use Raygun4php\RaygunRequestMessage;
use Raygun4php\Interfaces\TransportInterface;
use Raygun4php\Tests\Stubs\TransportGetMessageStub;

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

    /**
     * json schema used to validate message json.
     *
     * @var string
     */
    protected $jsonSchema;

    protected function setUp(): void
    {
        $this->transportMock = $this->getMockBuilder(TransportInterface::class)
                                    ->setMethods(['transmit'])
                                    ->getMock();

        $this->client = new RaygunClient($this->transportMock);
        $this->jsonSchema = file_get_contents('./tests/misc/RaygunSchema.json');
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

    public function testFilterAllFormValues()
    {
        $this->client->setFilterParams(array());
        $this->client->setFilterAllFormValues(true);
        $message = $this->getEmptyMessage();
        $message->Details->Request->Form = array(
            'MyParam' => 'some val',
            'MyRegexParam' => 'secret',
        );

        $filteredMessage = $this->client->filterParamsFromMessage($message);
        $this->assertEquals(
            $filteredMessage->Details->Request->Form,
            array(
                'MyParam' => '[filtered]',
                'MyRegexParam' => '[filtered]',
            )
        );
    }

    public function testFilterFormValuesDoesntFilterOtherValues()
    {
        $this->client->setFilterParams(array());
        $this->client->setFilterAllFormValues(true);
        $message = $this->getEmptyMessage();
        $message->Details->Request->Headers = array(
            'MyParam' => 'secret',
        );
        $message->Details->Request->Data = array(
            'MyParam' => 'secret',
        );

        $filteredMessage = $this->client->filterParamsFromMessage($message);
        $this->assertEquals(
            $filteredMessage->Details->Request->Headers,
            array('MyParam' => 'secret',)
        );
        $this->assertEquals(
            $filteredMessage->Details->Request->Data,
            array('MyParam' => 'secret',)
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

    public function testFilterIpAddress()
    {
        // Ensure IP is not filtered by default.
        $this->client->setFilterParams(array('SomethingElse' => true,));
        $message = $this->getEmptyMessage();
        $message->Details->Request->IpAddress = '0.0.0.0';

        $filteredMessage = $this->client->filterParamsFromMessage($message);
        $this->assertEquals(
            $filteredMessage->Details->Request->IpAddress,
            '0.0.0.0'
        );

        // Ensure IP can be filtered.
        $this->client->setFilterParams(array('IpAddress' => true,));
        $message = $this->getEmptyMessage();
        $message->Details->Request->IpAddress = '0.0.0.0';

        $filteredMessage = $this->client->filterParamsFromMessage($message);
        $this->assertEquals(
            $filteredMessage->Details->Request->IpAddress,
            '[filtered]'
        );
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

    public function testValidateMessageJsonInTransportObj()
    {
        $transportStub = new TransportGetMessageStub();
        $client = new RaygunClient($transportStub);

        $client->SendException(new \Exception('test'));
        $raygunMessage = $transportStub->getMessage();

        $data = json_decode($raygunMessage->toJson());

        $schemaValidator = new Validator();
        $schemaValidator->validate($data, $this->jsonSchema);
        $this->assertTrue($schemaValidator->isValid());
    }

    /**
     * @backupGlobals enabled
     */
    public function testServerUtf8Conversion()
    {
        $_SERVER = [
            'a' => 'hello',
            'b' => "\xc0\xde",
        ];
        $requestMessage = new RaygunRequestMessage();

        $this->assertSame('hello', $requestMessage->Data['a']);
        $this->assertSame('ÀÞ', $requestMessage->Data['b']);
    }
}
