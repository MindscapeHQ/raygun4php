<?php
namespace Raygun4php
{
  require_once realpath(__DIR__.'/RaygunMessage.php');

  class RaygunClient
  {
    protected $apiKey;

    public function __construct($key)
    {
      $this->apiKey = $key;
    }

    public function Send($errorException)
    {
        if (empty($this->apiKey))
        {
            throw new \Raygun4PhpException("Api key has not been provided, cannot send message");
        }

        $message = new RaygunMessage();
        $message->Build($errorException);
        $json = json_encode($message);

        $httpData = curl_init('https://api.raygun.io/entries');
        curl_setopt($httpData, CURLOPT_POSTFIELDS, $json);
        curl_setopt($httpData, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($httpData, CURLINFO_HEADER_OUT, true);
        curl_setopt($httpData, CURLOPT_HTTPHEADER, array(
            'X-ApiKey: '.$this->apiKey
        ));

        $result = curl_exec($httpData);
        if(curl_errno($httpData)){
            echo 'Cannot send Raygun message; curl error: ' . curl_error($httpData);
        }
        curl_close($httpData);
    }

    public function SendError($errno, $errstr, $errfile, $errline)
    {
        $this->Send(new \ErrorException($errstr, $errno, 0, $errfile, $errline));
    }

    public function SendException($exception)
    {
        $this->Send($exception);
    }
  }
}