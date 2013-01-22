<?php
namespace Raygun4php
{
/*
    Copyright (C) 2013 Mindscape

    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
    documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
    rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
    permit persons to whom the Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all copies or substantial portions
    of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
    THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
    TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
*/
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