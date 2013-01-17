<?php
namespace Raygun4php
{
  require_once realpath(__DIR__.'/RaygunMessage.php');

  class RaygunClient
  {
    protected $apiKey;

    public function __construct($key)
    {
      echo "Starting raygun client </br>";
      $this->apiKey = $key;
    }

    public function Send($exception)
    {
        if (empty($this->apiKey))
        {
            echo "No api key provided";
            throw new \Raygun4PhpException("Api key has not been provided, cannot send message");
        }

        $message = new RaygunMessage();
        $message->Build($exception);
    }
  }
}