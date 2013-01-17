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

        $json = json_encode($message);
        echo $json, "</br>";

        $httpData = curl_init('https://api.raygun.io/entries');
        curl_setopt($httpData, CURLOPT_POSTFIELDS, $json);
        curl_setopt($httpData, CURLOPT_POST, 1);
        curl_setopt($httpData, CURLOPT_VERBOSE, 1);
        curl_setopt($httpData, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($httpData, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($httpData, CURLINFO_HEADER_OUT, true);
        curl_setopt($httpData, CURLOPT_SSL_VERIFYHOST, 0);
        //curl_setopt($httpData, CURLOPT_CAPATH, getcwd().'/cert/');
        curl_setopt($httpData, CURLOPT_CAINFO, realpath(__DIR__.'/GeoTrustGlobalCA.crt'));
        curl_setopt($httpData, CURLOPT_HTTPHEADER, array(
            'X-ApiKey: '.$this->apiKey
        ));

        echo "about to send</br>";

        $result = curl_exec($httpData);
        if(curl_errno($httpData)){
            echo 'Curl error: ' . curl_error($httpData);
        }
        echo $result, '</br>';

        //echo curl_getinfo($httpData, CURLINFO_C  ), "</br>";
        print_r(curl_getinfo($httpData));

        echo "</br></br>Http code returned: ", curl_getinfo($httpData, CURLINFO_HTTP_CODE);

        //printf(curl_getinfo($httpData));
        curl_close($httpData);

        /*$content = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/json' . "\r\n"
                    . 'Content-Length: ' . strlen($json) . "\r\n"
                    . 'X-ApiKey: ' .$this->apiKey ."\r\n",
                'content' => $json,
            ),
        );

        $result = file_get_contents("https://api.raygun.io/entries", true, stream_context_create($content));*/

    }
  }
}