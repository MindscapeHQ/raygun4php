<?php
namespace Raygun4php
{
  require_once realpath(__DIR__.'/RaygunMessage.php');
  require_once realpath(__DIR__.'/RaygunIdentifier.php');
  require_once realpath(__DIR__.'/Raygun4PhpException.php');
  require_once realpath(__DIR__.'/Uuid.php');  

  use Rhumsaa\Uuid\Uuid;  

  class RaygunClient
  {
    protected $apiKey;
    protected $version;
    protected $tags;
    protected $user;    
    protected $httpData;

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
    * @param array $tags An optional array of string tags used to provide metadata for the message
    * @param array $userCustomData An optional associative array that can be used to place custom key-value
    * data in the message payload
    * @return The HTTP status code of the result when transmitting the message to Raygun.io
    */
    public function SendError($errno, $errstr, $errfile, $errline, $tags = null, $userCustomData = null, $timestamp = null)
    {
      if ($this->user == null)
      {
        $this->SetUser();
      }
      $message = $this->BuildMessage(new \ErrorException($errstr, $errno, 0, $errfile, $errline), $timestamp);

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
    * @param \Exception $exception An exception object to transmit
    * @param array $tags An optional array of string tags used to provide metadata for the message
    * @param array $userCustomData An optional associative array that can be used to place custom key-value
    * data in the message payload
    * @return The HTTP status code of the result when transmitting the message to Raygun.io
    */
    public function SendException($exception, $tags = null, $userCustomData = null, $timestamp = null)
    {
      if ($this->user == null)
      {
        $this->SetUser();
      }
      $message = $this->BuildMessage($exception, $timestamp);

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
    *  Stores the current user of the calling application. This will be added to any messages sent
    *  by this provider. It is used in the dashboard to provide unique user tracking.
    *  If it is an email address, the user's Gravatar can be displayed. This method is optional,
    *  if it is not used a random identifier will be assigned to the current user.
    *  @param string $user A username, email address or other identifier for the current user
    *  of the calling application.
    *
    */
    public function SetUser($user = null)
    {        
        if (is_string($user))
        {
            $this->user = $user;            
            if (php_sapi_name() != 'cli')
            {
              setcookie('rguserid', $user, time()+60*60*24*30);
              setcookie('rguuid', 'false', time()+60*60*24*30);
            }            
        }     
        else
        {                    
          if (!array_key_exists('rguuid', $_COOKIE))
          {                    
            $this->user = (string) Uuid::uuid4();
            if (php_sapi_name() != 'cli')
            {
              setcookie('rguserid', $this->user, time()+60*60*24*30);
              setcookie('rguuid', 'true', time()+60*60*24*30);
            }
          }
          else
          {
            $this->user = $_COOKIE['rguserid'];            
          }                
        }
    }

    /*
     * Sets a string array of tags relating to the message,
     * used for identification. These will be transmitted along with messages that
     * are sent.
     * @param array $tags The tags relating to your project's version
    */
    private function BuildMessage($errorException, $timestamp = null)
    {
        $message = new RaygunMessage($timestamp);
        $message->Build($errorException);
        $message->Details->Version = $this->version;        
        $message->Details->Context = new RaygunIdentifier(session_id());
        
        if ($this->user != null)
        {
          $message->Details->User = new RaygunIdentifier($this->user);
        }
        else 
        {                    
          $message->Details->User = new RaygunIdentifier($_COOKIE['rguserid']);                    
        }                        
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
        if ($this->is_assoc($userCustomData))
        {
            $message->Details->UserCustomData = $userCustomData;
        }
        else
        {
            throw new \Raygun4php\Raygun4PhpException("UserCustomData must be an associative array");
        }
    }

    private function is_assoc($array) {
      return (bool)count(array_filter(array_keys($array), 'is_string'));
    }

    /*
     * Transmits an exception or ErrorException to the Raygun.io API
     * @throws Raygun4php\Raygun4PhpException
     * @param Raygun4php\RaygunMessage $message A populated message to be posted to the Raygun API
     * @return The HTTP status code of the result after transmitting the message to Raygun.io
     * 200 if accepted, 403 if invalid JSON payload
     */
    public function Send($message)
    {
        if (!function_exists('curl_version'))
        {
          throw new \Raygun4php\Raygun4PhpException("cURL is not available, thus Raygun4php cannot send. Please install and enable cURL in your PHP server.");
        }
        else if (empty($this->apiKey))
        {
            throw new \Raygun4php\Raygun4PhpException("API not valid, cannot send message to Raygun");
        }
        else
        {         
          if (!$this->httpData) {
              $this->httpData = curl_init('https://api.raygun.io/entries');
          }
          curl_setopt($this->httpData, CURLOPT_POSTFIELDS, json_encode($message));
          curl_setopt($this->httpData, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($this->httpData, CURLINFO_HEADER_OUT, true);
          curl_setopt($this->httpData, CURLOPT_CAINFO, realpath(__DIR__.'/cacert.crt'));
          curl_setopt($this->httpData, CURLOPT_HTTPHEADER, array(
              'X-ApiKey: '.$this->apiKey
          ));

          curl_exec($this->httpData);
          $info = curl_getinfo($this->httpData);

          return $info['http_code'];
      }
    }

      public function __destruct()
      {
          if ($this->httpData)
              curl_close($this->httpData);
      }
  }
}