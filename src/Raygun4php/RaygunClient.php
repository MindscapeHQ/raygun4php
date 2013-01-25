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

    /*
     * Transmits an exception or ErrorException to the Raygun.io API
     * @throws Raygun4php\Raygun4PhpException
     * @param \ErrorException $errorException
     */
    public function Send($errorException)
    {
        if (empty($this->apiKey))
        {
            throw new \Raygun4PhpException("API not valid, cannot send message to Raygun");
        }

        $message = new RaygunMessage();
        $message->Build($errorException);
        $json = json_encode($message);

        $httpData = curl_init('https://api.raygun.io/entries');
        curl_setopt($httpData, CURLOPT_POSTFIELDS, $json);
        curl_setopt($httpData, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($httpData, CURLINFO_HEADER_OUT, true);
        curl_setopt($httpData, CURLOPT_CAINFO, realpath(__DIR__.'/cacert.crt'));
        curl_setopt($httpData, CURLOPT_HTTPHEADER, array(
            'X-ApiKey: '.$this->apiKey
        ));

        curl_exec($httpData);
        $info = curl_getinfo($httpData);

        curl_close($httpData);
        return $info['http_code'];
    }

    /*
     * Transmits an error to the Raygun.io API
     * @param int $errorno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     */
    public function SendError($errno, $errstr, $errfile, $errline)
    {
        return $this->Send(new \ErrorException($errstr, $errno, 0, $errfile, $errline));
    }

    /*
     * Transmits an exception to the Raygun.io API
     * @param \Exception $exception
     */
    public function SendException($exception)
    {
        return $this->Send($exception);
    }
  }
}