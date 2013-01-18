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

    public function Send($exception)
    {
        if (empty($this->apiKey))
        {
            throw new \Raygun4PhpException("Api key has not been provided, cannot send message");
        }

        $message = new RaygunMessage();
        $message->Build($exception);
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
            echo 'Curl error: ' . curl_error($httpData);
        }
        curl_close($httpData);
    }
  }
}