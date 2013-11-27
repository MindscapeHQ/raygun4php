<?php
require_once realpath(__DIR__ . '../src/Raygun4php/Raygun4PhpException.php');

class RaygunMessageTest extends PHPUnit_Framework_TestCase
{

  /**
   * @expectedException Raygun4PhpException
   * @expectedExceptionMessage API not valid, cannot send message to Raygun
   */
  public function testSendReturns403WithInvalidApiKey()
  {
    $client = new \Raygun4php\RaygunClient("", true);
    $client->SendException(new Exception(''));
  }
}
?>