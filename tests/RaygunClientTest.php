<?php

class RaygunClientTest extends PHPUnit_Framework_TestCase
{

  /**
   * @expectedException \Raygun4php\Raygun4PhpException
   * @expectedExceptionMessage API not valid, cannot send message to Raygun
   */
  public function testSendReturns403WithInvalidApiKey()
  {
    $client = new \Raygun4php\RaygunClient("", true);
    $client->SendException(new Exception(''));
  }

  public function testGetFilteredParamsRemovesByKey() {
    $client = new \Raygun4php\RaygunClient("some-api-key", true);
    $client->setFilterParams(array(
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

    $filteredMessage = $client->filterParamsFromMessage($message);
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

  public function testGetFilteredParamsIgnoresCase() {
    $client = new \Raygun4php\RaygunClient("some-api-key", true);
    $client->setFilterParams(array('myparam' => true));
    $message = $this->getEmptyMessage();
    $message->Details->Request->Form = array('MyParam' => 'secret',);

    $filteredMessage = $client->filterParamsFromMessage($message);
    $this->assertEquals(
        $filteredMessage->Details->Request->Form,
        array('MyParam' => '[filtered]',)
    );
  }

  public function testGetFilteredParamsAcceptsCustomFunctions() {
    $client = new \Raygun4php\RaygunClient("some-api-key", true);
    $client->setFilterParams(array(
        'MyParam' => function($key, $val) {return strrev($val);},
    ));
    $message = $this->getEmptyMessage();
    $message->Details->Request->Form = array(
        'MyParam' => 'secret',
    );

    $filteredMessage = $client->filterParamsFromMessage($message);
    $this->assertEquals(
        $filteredMessage->Details->Request->Form,
        array(
            'MyParam' => 'terces',
        )
    );
  }

  public function testGetFilteredParamsRemovesRawData() {
    $client = new \Raygun4php\RaygunClient("some-api-key", true);
    $message = $this->getEmptyMessage();
    $message->Details->Request->RawData = 'some-data';

    $filteredMessage = $client->filterParamsFromMessage($message);
    $this->assertEquals($filteredMessage->Details->Request->RawData, 'some-data');

    $client->setFilterParams(array('MySensitiveParam' => true));
    $filteredMessage = $client->filterParamsFromMessage($message);
    $this->assertNull($filteredMessage->Details->Request->RawData);
  }

  public function testGetFilteredParamsParsesRegex() {
    $client = new \Raygun4php\RaygunClient("some-api-key", true);
    $client->setFilterParams(array('/MyRegex.*/' => true,));
    $message = $this->getEmptyMessage();
    $message->Details->Request->Form = array(
        'MyParam' => 'some val',
        'MyRegexParam' => 'secret',
    );

    $filteredMessage = $client->filterParamsFromMessage($message);
    $this->assertEquals(
        $filteredMessage->Details->Request->Form,
        array(
            'MyParam' => 'some val',
            'MyRegexParam' => '[filtered]',
        )
    );
  }

  protected function getEmptyMessage() {
    $requestMessage = new Raygun4php\RaygunRequestMessage();
    $requestMessage->HostName = null;
    $requestMessage->Url = null;
    $requestMessage->HttpMethod = null;
    $requestMessage->IpAddress = null;
    $requestMessage->QueryString = null;
    $requestMessage->Headers = null;
    $requestMessage->Data = null;
    $requestMessage->RawData = null;
    $requestMessage->Form = null;
    $message = new Raygun4php\RaygunMessage(0);
    $message->Details->Request = $requestMessage;

    return $message;
  }
}
?>