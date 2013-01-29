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
    * Transmits an error to the Raygun.io API
    * @param int $errorno The error number
    * @param string $errstr The error string
    * @param string $errfile The file the error occurred in
    * @param int $errline The line the error occurred on
    * @return The HTTP status code of the result when transmitting the message to Raygun.io
    */
    public function SendError($errno, $errstr, $errfile, $errline, $tags = null, $userCustomData = null)
    {
      $message = $this->BuildMessage(new \ErrorException($errstr, $errno, 0, $errfile, $errline));

      if ($tags != null)
      {
          $this->AddTags($message, $tags);
      }
      if ($userCustomData != null)
      {
          $this->AddUserCustomData($message, $userCustomData);
      }
      return $this->Send($message);
    }

    /*
    * Transmits an exception to the Raygun.io API
    * @param \Exception $exception
    * @return The HTTP status code of the result when transmitting the message to Raygun.io
    */
    public function SendException($exception, $tags = null, $userCustomData = null)
    {
      $message = $this->BuildMessage($exception);

      if ($tags != null)
      {
          $this->AddTags($message, $tags);
      }
      if ($userCustomData != null)
      {
          $this->AddUserCustomData($message, $userCustomData);
      }

      $result = $this->Send($message);
      return $result;
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
     * Sets a string array of tags relating to the message,
     * used for identification. These will be transmitted along with messages that
     * are sent.
     * @param array $tags The tags relating to your project's version
    */

    private function BuildMessage($errorException)
    {
        $message = new RaygunMessage();
        $message->Build($errorException);
        $message->Details->Version = $this->version;
        return $message;
    }

    private function AddTags(&$message, $tags)
    {
        if (is_array($tags))
        {
            $message->Details->Tags = $tags;
        }
        else
        {
            throw new \Raygun4php\Raygun4PhpException("Tags must be an array");
        }
    }

    private function AddUserCustomData(&$message, $userCustomData)
    {
        if (is_array($userCustomData))
        {
            $message->Details->UserCustomData = $userCustomData;
        }
        else
        {
            throw new \Raygun4php\Raygun4PhpException("UserCustomData must be an array");
        }
    }

    /*
     * Transmits an exception or ErrorException to the Raygun.io API
     * @throws Raygun4php\Raygun4PhpException
     * @param \ErrorException $errorException
     * @return The HTTP status code of the result when transmitting the message to Raygun.io
     */
    public function Send($message)
    {
        if (empty($this->apiKey))
        {
            throw new \Raygun4php\Raygun4PhpException("API not valid, cannot send message to Raygun");
        }

        $httpData = curl_init('https://api.raygun.io/entries');
        curl_setopt($httpData, CURLOPT_POSTFIELDS, json_encode($message));
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
  }
}