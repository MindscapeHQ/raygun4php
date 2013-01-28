<?php
namespace Raygun4php
{
  require_once realpath(__DIR__.'/RaygunMessage.php');

  class RaygunClient
  {
    protected $apiKey;
    protected $version;
    protected $tags;

    public function __construct($key)
    {
        $this->apiKey = $key;
    }

    /*
     * Sets the version number of your project that will be transmitted
     * to Raygun.io.
     * @param string $version The version number in the form of x.x.x.x,
     * where x is a positive integer.
     *
     */
    public function SetVersion($version)
    {
        $this->version = $version;
    }

    /*
     * Sets a string array of tags relating to the version of your project,
     * used for identification. These will be transmitted along with messages that
     * are sent.
     * @param array $tags The tags relating to your project's version
    */
    public function SetTags($tags)
    {
        if (is_array($tags))
        {
            $this->tags = $tags;
        }
        else
        {
            throw new \Raygun4php\Raygun4PhpException("Tags must be an array");
        }
    }

    /*
     * Transmits an exception or ErrorException to the Raygun.io API
     * @throws Raygun4php\Raygun4PhpException
     * @param \ErrorException $errorException
     * @return The HTTP status code of the result when transmitting the message to Raygun.io
     */
    public function Send($errorException)
    {
        if (empty($this->apiKey))
        {
            throw new \Raygun4php\Raygun4PhpException("API not valid, cannot send message to Raygun");
        }

        $message = new RaygunMessage();
        $message->Build($errorException);
        $message->Details->Version = $this->version;
        $message->Details->Tags = $this->tags;
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
     * @return The HTTP status code of the result when transmitting the message to Raygun.io
     */
    public function SendError($errno, $errstr, $errfile, $errline)
    {
        return $this->Send(new \ErrorException($errstr, $errno, 0, $errfile, $errline));
    }

    /*
     * Transmits an exception to the Raygun.io API
     * @param \Exception $exception
     * @return The HTTP status code of the result when transmitting the message to Raygun.io
     */
    public function SendException($exception)
    {
        return $this->Send($exception);
    }
  }
}