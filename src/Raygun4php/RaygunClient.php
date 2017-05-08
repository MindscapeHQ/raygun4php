<?php 
namespace Raygun4php {
  require_once realpath(__DIR__ . '/RaygunMessage.php');
  require_once realpath(__DIR__ . '/RaygunIdentifier.php');
  require_once realpath(__DIR__ . '/Raygun4PhpException.php');
  require_once realpath(__DIR__ . '/RaygunErrorBundler.php');
  require_once realpath(__DIR__ . '/Uuid.php');

  use Raygun4Php\Rhumsaa\Uuid\Uuid;

  class RaygunClient
  {
    protected $apiKey;
    protected $version;
    protected $tags;
    protected $user;
    protected $firstName;
    protected $fullName;
    protected $email;
    protected $isAnonymous;
    protected $uuid;
    protected $httpData;
    protected $useAsyncSending;
    protected $debugSending;
    protected $disableUserTracking;
    protected $proxy;

    protected $groupingKeyCallback;

    protected $cookieOptions = array(
      'use'      => true,
      'expire'   => 2592000, // 30 * 24 * 60 * 60
      'path'     => '/',
      'domain'   => '',
      'secure'   => false,
      'httponly' => false
    );

    /**
     * @var Array Parameter names to filter out of logged form data. Case insensitive.
     * Accepts regular expressions when the name starts with a forward slash.
     * Maps either to TRUE, or to a callable with $key and $value arguments.
     */
    protected $filterParams = array();

    private $host = 'api.raygun.io';
    private $path = '/entries';
    private $transport = 'ssl';
    private $port = 443;
    private $bundleErrors = false;
    private $settings;

    /*
    * Creates a new RaygunClient instance.
    * @param bool $useAsyncSending If true, attempts to post rapidly and asynchronously the script by forking a cURL process.
    * RaygunClient cannot return the HTTP result when in async mode, however. If false, sends using a blocking socket connection.
    * This is the only method available on Windows.
    * @param bool $debugSending If true, and $useAsyncSending is true, this will output the HTTP response code from posting
    * @param bool $disableUserTracking If true, no user data is sent to the API
    * ---
    * @array $options 
    * @param callable $beforeSendCallback A callable method to run before the error is sent to the API, with $message as a parameter
    * @param bool $bundleErrors If true, errors will be bundled and sent as a JSON object, useful for high traffic
    * @param int $maxBundleSize The maximum number of errors to include in a bundle before sending. Maximum value is 100 if gzip is enabled, 10 if not enabled
    * @param bool $gzipBundle Gzip compress the JSON bundle before sending
    * @param bool $encodeData Whether to base64 encode data sent to the API
    * @param bool $writeToDisk Whether to write the bundle overflow to disk, if false, will use session storage if available
    */
    public function __construct($key, $useAsyncSending = true, $debugSending = false, $disableUserTracking = false, $options = array())
    {
      $this->apiKey = $key;
      $this->useAsyncSending = $useAsyncSending;
      $this->debugSending = $debugSending;

      $defaults = array(
        "beforeSendCallback" => null,
        "bundleErrors" => false,
        "maxBundleSize" => 100,
        "gzipBundle" => true,
        "encodeData" => true,
        "writeToDisk" => false
      );

      $this->settings = array_merge($defaults, $options);

      // If gzip is not enabled, set the maximum bundle size to 10 to avoid curl error (i.e. command becomes too large)
      if($this->settings["gzipBundle"]) {
        $this->settings["maxBundleSize"] = min(100, $this->settings["maxBundleSize"]);
      }
      else {
        $this->settings["maxBundleSize"] = min(10, $this->settings["maxBundleSize"]);
      }

      if($this->settings["bundleErrors"]) {
        $this->path = '/entries/bulk';

        $this->bundler = new \Raygun4php\RaygunErrorBundler($this->settings);

        $this->flushBundle();
      }

      if (!$disableUserTracking) {
        $this->SetUser();
      }

      $this->disableUserTracking = $disableUserTracking;
    }

    /*
    * Transmits an error to the Raygun.io API
    * @param int $errorno The error number
    * @param string $errstr The error string
    * @param string $errfile The file the error occurred in
    * @param int $errline The line the error occurred on
    * @param array $tags An optional array of string tags used to provide metadata for the message
    * @param array $userCustomData An optional associative array that can be used to place custom key-value
    * @param int $timestamp Current Unix timestamp in the local timezone, used to set when an error occurred.
    * data in the message payload
    * @return The HTTP status code of the result when transmitting the message to Raygun.io
    */
    public function SendError($errno, $errstr, $errfile, $errline, $tags = null, $userCustomData = null, $timestamp = null)
    {
      $message = $this->BuildMessage(new \ErrorException($errstr, $errno, 0, $errfile, $errline), $timestamp);

      if ($tags != null)
      {
        $this->AddTags($message, $tags);
      }

      if ($userCustomData != null)
      {
        $this->AddUserCustomData($message, $userCustomData);
      }

      $this->AddGroupingKey($message);

      return $this->Send($message);
    }

    /*
    * Transmits an exception to the Raygun.io API
    * @param \Exception $exception An exception object to transmit
    * @param array $tags An optional array of string tags used to provide metadata for the message
    * @param array $userCustomData An optional associative array that can be used to place custom key-value
    * data in the message payload
    * @param int $timestamp Current Unix timestamp in the local timezone, used to set when an exception occurred.
    * @return The HTTP status code of the result when transmitting the message to Raygun.io
    */
    public function SendException($exception, $tags = null, $userCustomData = null, $timestamp = null)
    {
      $message = $this->BuildMessage($exception, $timestamp);

      if ($tags != null)
      {
        $this->AddTags($message, $tags);
      }

      if ($userCustomData != null)
      {
        $this->AddUserCustomData($message, $userCustomData);
      }

      $this->AddGroupingKey($message);

      return $this->Send($message);
    }

    /*
     * Sets the version number of your project that will be transmitted
     * to Raygun.com.
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
    public function SetUser($user = null, $firstName = null, $fullName = null, $email = null, $isAnonymous = null, $uuid = null)
    {
      $this->firstName = $this->StoreOrRetrieveUserCookie('rgfirstname', $firstName);
      $this->fullName = $this->StoreOrRetrieveUserCookie('rgfullname', $fullName);
      $this->email = $this->StoreOrRetrieveUserCookie('rgemail', $email);

      $this->uuid = $this->StoreOrRetrieveUserCookie('rguuidvalue', $uuid);
      $this->isAnonymous = $this->StoreOrRetrieveUserCookie('rgisanonymous', $isAnonymous ? 'true' : 'false') == 'true' ? true : false;

      if (is_string($user))
      {
        $this->user = $user;

        if (php_sapi_name() != 'cli' && !headers_sent())
        {
          $this->setCookie('rguserid', $user);
          $this->setCookie('rguuid', 'false');
        }
      }
      else
      {
        if (!array_key_exists('rguuid', $_COOKIE))
        {
          $this->user = (string)Uuid::uuid4();

          if (php_sapi_name() != 'cli' && !headers_sent())
          {
            $this->setCookie('rguserid', $this->user);
            $this->setCookie('rguuid', 'true');
          }
        }
        else if (array_key_exists('rguserid', $_COOKIE))
        {
          $this->user = $_COOKIE['rguserid'];
        }

        $this->isAnonymous = $this->StoreOrRetrieveUserCookie('rgisanonymous', 'true') == 'true';
      }
    }

    /*
    * Sets a callback to control how error instances are grouped together. The callback
    * is provided with the payload and stack trace of the error upon execution. If the
    * callback returns a string then error instances with a matching key will grouped together.
    * If the callback doesn't return a value, or the value is not a string, then automatic
    * grouping will be used.
    * @param function $callback
    *
    */
    public function SetGroupingKey($callback) {
      $this->groupingKeyCallback = $callback;
    }

    private function StoreOrRetrieveUserCookie($key, $value)
    {
      if (is_string($value))
      {
        if (php_sapi_name() != 'cli' && !headers_sent())
        {
          $this->setCookie($key, $value);
        }

        return $value;
      }
      else
      {
        if (array_key_exists($key, $_COOKIE))
        {
          if ($_COOKIE[$key] != $value && php_sapi_name() != 'cli' && !headers_sent())
          {
            $this->setCookie($key, $value);
          }
          return $_COOKIE[$key];
        }
      }

      return null;
    }

    /**
     * @param string $name
     * @param string $value
     */
    protected function setCookie($name, $value)
    {
      $options = $this->cookieOptions;

      if ($options['use'] === true) {
        setcookie($name, $value, time() + $options['expire'], $options['path'], $options['domain'], $options['secure'], $options['httponly']);
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
        $message->Details->User = new RaygunIdentifier($this->user, $this->firstName, $this->fullName, $this->email, $this->isAnonymous, $this->uuid);
      }
      else if (!$this->disableUserTracking)
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

    private function AddGroupingKey(&$message) {
      if( is_callable( $this->groupingKeyCallback ) ) {
        $groupingKey = call_user_func( $this->groupingKeyCallback, $message, $message->Details->Error->StackTrace );

        if( is_string( $groupingKey ) ) {
          $message->Details->GroupingKey = $groupingKey;
        }
      }
    }

    private function is_assoc($array)
    {
      return (bool)count(array_filter(array_keys($array), 'is_string'));
    }

    /**
     * flushBundle
     *
     * Flush any leftover messages stored in the global session which weren't bundled
     *
     * @return [void]
     */

    private function flushBundle() {
      $bundle = $this->bundler->getFromStorage();

      if(!empty($bundle)) {
        $this->bundler->setBundle($bundle);

        if(count($bundle) === 1) {
          $this->Send($bundle[0]);
        }
        else {
          $this->SendBundle($bundle);
        }
      }
    }

    /**
     * SendBundle
     *
     * - Get the current bundled data in memory as a JSON object
     * - Reset the bundle in memory
     * - Post data to the API
     *
     * @return [mixed] [Result of the post() method]
     */

    private function SendBundle() {
      $dataToSend = $this->bundler->getJson();
      $this->bundler->reset();
      return $this->post($dataToSend, realpath(__DIR__ . '/cacert.crt'));
    }

    /**
     * OnBeforeSend 
     *
     * Allow a callback to be called to manipulate the message before it gets sent to the API. 
     *
     * @param [RaygunMessage] $message [The message object]
     * @param [string] $callback [User defined callable]
     */
    private function OnBeforeSend($message, $callback = null) {
      if(is_callable($callback)) {
        $message = $callback($message);
      }

      return $message;
    }

    /*
     * Transmits a RaygunMessage to the Raygun.io API. The default attempts to transmit asynchronously.
     * To disable this and transmit sync (blocking), pass false in as the 2nd parameter in RaygunClient's
     * constructor. This may be necessary on some Windows installations where the implementation is broken.
     * This is a lower level function used by SendException and SendError and one of those should be used preferrably.
     * @param Raygun4php\RaygunMessage $message A populated message to be posted to the Raygun API
     * @return The HTTP status code of the result after transmitting the message to Raygun.io
     * 202 if accepted, 403 if invalid JSON payload
     */
    public function Send($message)
    {
      $message = $this->OnBeforeSend($message, $this->settings["beforeSendCallback"]);

      if($message === false || empty($message)) {
        return;
      }

      if (empty($this->apiKey))
      {
        throw new \Raygun4php\Raygun4PhpException("API not valid, cannot send message to Raygun");
      }

      $message = $this->filterParamsFromMessage($message);
      $message = $this->toJsonRemoveUnicodeSequences($message);

      if($this->settings["bundleErrors"]) {
        $this->bundler->addMessage($message);

        if($this->bundler->isReadyToSend()) {
          return $this->SendBundle();
        }

        return;
      }

      return $this->post($message, realpath(__DIR__ . '/cacert.crt'));
    }

    private function post($data_to_send, $cert_path)
    {
      $headers = 0;
      $remote = $this->transport . '://' . $this->host . ':' . $this->port;

      $context = stream_context_create();
      $result = stream_context_set_option($context, 'ssl', 'verify_host', true);

      if (!empty($cert_path))
      {
        $result = stream_context_set_option($context, 'ssl', 'cafile', $cert_path);
        $result = stream_context_set_option($context, 'ssl', 'verify_peer', true);
      }
      else
      {
        $result = stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
      }

      if ($this->proxy) {
        $result = stream_context_set_option($context, 'http', 'proxy', $this->proxy);
      }

      if ($this->useAsyncSending && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
      {
        $curlOpts = array();

        if($this->settings["gzipBundle"]) {

          $curlOpts = array(
            "-X POST", 
            "-d '{$data_to_send}'",
            "-H 'Content-Encoding: gzip'", 
            "-H 'Content-Type: application/x-www-form-urlencoded'",
            "-H 'X-ApiKey: {$this->apiKey}'",
          );

        }
        else {
          $curlOpts = array(
            "-X POST",
            "-H 'Content-Type: application/json'",
            "-H 'X-ApiKey: {$this->apiKey}'",
            "-d " . escapeshellarg($data_to_send),
          );
        }

        if($this->settings["encodeData"]) {
          $curlOpts[] = "-H 'X-Base64Encoded: true'";
        }

        $curlOpts[] = "--cacert '" . realpath(__DIR__ . '/cacert.crt') . "'";

        if ($this->proxy) {
          $curlOpts[] = "--proxy '" . $this->proxy . "'";
        }

        $transport = $this->transport === "ssl" ? "https" : "http";
        $endpoint = "{$transport}://{$this->host}{$this->path}";

        $cmd = "curl " . implode(' ', $curlOpts) . " '{$endpoint}' > /dev/null 2>&1 &";

        exec($cmd, $output, $exit);
        
        return $exit;
      }
      else
      {
        $fp = stream_socket_client($remote, $err, $errstr, 10, STREAM_CLIENT_CONNECT, $context);

        if ($fp)
        {
          $req = '';
          $req .= "POST $this->path HTTP/1.1\r\n";
          $req .= "Host: $this->host\r\n";
          $req .= "X-ApiKey: " . $this->apiKey . "\r\n";
          $req .= 'Content-length: ' . strlen($data_to_send) . "\r\n";
          $req .= "Content-type: application/json\r\n";
          $req .= "Connection: close\r\n\r\n";
          fwrite($fp, $req);
          fwrite($fp, $data_to_send);

          $response = "";
          if ($this->debugSending)
          {
            while(!preg_match("/^HTTP\/[\d\.]* (\d{3})/", $response))
            {
              $response .= fgets($fp, 128);
            }

            fclose($fp);

            return $response;
          }
          else
          {
            fclose($fp);

            return 0;
          }
        }
        else
        {
          $errMsg = "<br/><br/>" . "<strong>Raygun Warning:</strong> Couldn't send asynchronously. ";
          $errMsg .= "Try calling new RaygunClient('apikey', FALSE); to use an alternate sending method, or RaygunClient('key', FALSE, TRUE) to echo the HTTP response" . "<br/><br/>";
          echo $errMsg;
          trigger_error('httpPost error: ' . $errstr);
          return null;
        }
      }
    }

    function toJsonRemoveUnicodeSequences($struct) {
      return preg_replace_callback("/\\\\u([a-f0-9]{4})/", function($matches){ return iconv('UCS-4LE','UTF-8',pack('V', hexdec("U$matches[1]"))); }, json_encode($struct));
    }

    /**
     * Optionally applies a value transformation to every matching key, as defined by {@link FilterParams}.
     * Replaces the value by default, but also supports custom transformations through
     * anonymous functions. Applies to form data, environment data, HTTP headers.
     * Does not apply to GET parameters in the request URI.
     * Filters out raw HTTP data in case any filters are defined, since we can't accurately filter it.
     *
     * @param RaygunMessage $message
     * @param  string $replace Value to be inserted by default (unless specified otherwise by custom transformations).
     * @return RaygunMessage
     */
    function filterParamsFromMessage($message, $replace = '[filtered]') {
      $filterParams = $this->getFilterParams();

      // Skip checks if none are defined
      if(!$filterParams) {
        return $message;
      }

      // Ensure all filters are callable
      foreach($filterParams as $filterKey => $filterFn) {
        if(!is_callable($filterFn)) {
          $filterParams[$filterKey] = function($key, $val) use ($replace) {return $replace;};
        }
      }

      $walkFn = function(&$val, $key) use ($filterParams) {
        foreach($filterParams as $filterKey => $filterFn) {
          if(
            (strpos($filterKey, '/') === 0 && preg_match($filterKey, $key))
            || (strpos($filterKey, '/') === FALSE && strtolower($filterKey) == strtolower($key))
          ) {
            $val = $filterFn($key, $val);
          }
        }
      };

      if($message->Details->Request->Form) {
        array_walk_recursive($message->Details->Request->Form, $walkFn);
      }

      if($message->Details->Request->Headers) {
        array_walk_recursive($message->Details->Request->Headers, $walkFn);
      }

      if($message->Details->Request->Data) {
        array_walk_recursive($message->Details->Request->Data, $walkFn);
      }

      if($message->Details->UserCustomData) {
        array_walk_recursive($message->Details->UserCustomData, $walkFn);
      }

      // Unset raw HTTP data since we can't accurately filter it
      if($message->Details->Request->RawData) {
        $message->Details->Request->RawData = null;
      }

      return $message;
    }

    /**
     * @param Array $params
     * @return Raygun4php\RaygunClient
     */
    function setFilterParams($params) {
      $this->filterParams = $params;
      return $this;
    }

    /**
     * @return Array
     */
    function getFilterParams() {
      return $this->filterParams;
    }

    /**
     * Use a proxy for sending HTTP requests to Raygun.
     *
     * @param String $url URL including protocol and an optional port, e.g. http://myproxy:8080
     * @return Raygun4php\RaygunClient
     */
    function setProxy($proxy) {
      $this->proxy = $proxy;
      return $this;
    }

    /**
     * Sets the given cookie options
     *
     * Existing values will be overridden. Values that are missing from the array being set will keep their current
     * values.
     *
     * The key names match the argument names on setcookie() (e.g. 'expire' or 'path'). Pass the default value according
     * to PHP's setcookie() function to ignore that parameter.
     *
     * @param array<string,mixed> $options
     */
    public function SetCookieOptions($options)
    {
      $this->cookieOptions = array_merge($this->cookieOptions, $options);
    }

    /**
     * @return String
     */
    function getProxy() {
      return $this->proxy;
    }

    public function __destruct()
    {
      if ($this->httpData)
      {
        curl_close($this->httpData);
      }
    }

  }
}
