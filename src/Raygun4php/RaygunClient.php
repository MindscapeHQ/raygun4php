<?php
namespace Raygun4php {
    require_once realpath(__DIR__ . '/RaygunMessage.php');
    require_once realpath(__DIR__ . '/RaygunIdentifier.php');
    require_once realpath(__DIR__ . '/Raygun4PhpException.php');
    require_once realpath(__DIR__ . '/Uuid.php');

    class RaygunClient
    {
        protected $apiKey;
        protected $httpData;
        protected $useAsyncSending;

        protected $messageBuilder;

        public function __construct($key, $useAsyncSending = true)
        {
            $this->apiKey = $key;
            $this->useAsyncSending = $useAsyncSending;

            $this->messageBuilder = new RaygunMessageBuilder();
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
            $message = $this->messageBuilder->BuildMessage(new \ErrorException($errstr, $errno, 0, $errfile, $errline), $timestamp);

            if ($tags != null) {
                $this->messageBuilder->AddTagsToMessage($message, $tags);
            }
            if ($userCustomData != null) {
                $this->messageBuilder->AddUserCustomDataToMessage($message, $userCustomData);
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
            $message = $this->messageBuilder->BuildMessage($exception, $timestamp);

            if ($tags != null) {
                $this->messageBuilder->AddTagsToMessage($message, $tags);
            }
            if ($userCustomData != null) {
                $this->messageBuilder->AddUserCustomDataToMessage($message, $userCustomData);
            }

            return $this->Send($message);
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
            $this->messageBuilder->SetVersion($version);
        }


        /*
         * Transmits an exception or ErrorException to the Raygun.io API. The default attempts to transmit asynchronously.
         * To disable this and transmit sync (blocking), pass false in as the 2nd parameter in RaygunClient's
         * constructor. This may be necessary on some Windows installations where the implementation is broken.
         * @param Raygun4php\RaygunMessage $message A populated message to be posted to the Raygun API
         * @return The HTTP status code of the result after transmitting the message to Raygun.io
         * 202 if accepted, 403 if invalid JSON payload
         */
        public function Send($message)
        {
            if (empty($this->apiKey)) {
                throw new \Raygun4php\Raygun4PhpException("API not valid, cannot send message to Raygun");
            }

            return $this->postAsync(
                'api.raygun.io',
                '/entries',
                json_encode($message),
                realpath(__DIR__ . '/cacert.crt')
            );
        }

        private function postAsync(
            $host,
            $path,
            $data_to_send,
            $cert_path,
            $opts = array('headers' => 0, 'transport' => 'ssl', 'port' => 443)
        ) {
            $transport = '';
            $port = 80;
            if (!empty($opts['transport'])) {
                $transport = $opts['transport'];
            }
            if (!empty($opts['port'])) {
                $port = $opts['port'];
            }
            $remote = $transport . '://' . $host . ':' . $port;

            $context = stream_context_create();
            $result = stream_context_set_option($context, 'ssl', 'verify_host', true);
            if (!empty($cert_path)) {
                $result = stream_context_set_option($context, 'ssl', 'cafile', $cert_path);
                $result = stream_context_set_option($context, 'ssl', 'verify_peer', true);
            } else {
                $result = stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
            }

            if ($this->useAsyncSending && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $cmd = "curl -X POST -H 'Content-Type: application/json' -H 'X-ApiKey: " . $this->apiKey . "'";
                $cmd .= " -d '" . $data_to_send . "' --cacert '" . realpath(__DIR__ . '/cacert.crt')
                     . "' 'https://api.raygun.io:443/entries' > /dev/null 2>&1 &";

                exec($cmd, $output, $exit);
                return $exit;
            } else {
                $fp = stream_socket_client($remote, $err, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
                if ($fp) {
                    $req = '';
                    $req .= "POST $path HTTP/1.1\r\n";
                    $req .= "Host: $host\r\n";
                    $req .= "X-ApiKey: " . $this->apiKey . "\r\n";
                    $req .= 'Content-length: ' . strlen($data_to_send) . "\r\n";
                    $req .= "Content-type: application/json\r\n";
                    $req .= "Connection: close\r\n\r\n";
                    fwrite($fp, $req);
                    fwrite($fp, $data_to_send);
                    fclose($fp);
                    return 202;
                } else {
                    echo "<br/><br/>" . "<strong>Raygun Warning:</strong> Couldn't send asynchronously. Try calling new RaygunClient('apikey', FALSE); to use an alternate sending method" . "<br/><br/>";
                    trigger_error('httpPost error: ' . $errstr);
                    return null;
                }
            }
        }

        public function __destruct()
        {
            if ($this->httpData) {
                curl_close($this->httpData);
            }
        }
    }
}