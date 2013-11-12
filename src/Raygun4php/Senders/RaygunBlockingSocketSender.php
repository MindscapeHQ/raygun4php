<?php

namespace Raygun4php\Senders;

use Raygun4php\RaygunMessage;

class RaygunBlockingSocketSender implements RaygunMessageSender
{

    private $apiKey;
    private $host;
    private $end_point;
    private $cert_path;
    private $opts;


    function __construct(
        $apiKey,
        $host,
        $end_point,
        $cert_path,
        $opts = array('headers' => 0, 'transport' => 'ssl', 'port' => 443)
    )
    {
        $this->apiKey = $apiKey;
        $this->host = $host;
        $this->end_point = $end_point;
        $this->cert_path = $cert_path;
        $this->opts = $opts;
    }


    public function Send(RaygunMessage $message)
    {
        $data_to_send = json_encode($message);
        $remote = $this->buildRemotePath($this->host, $this->opts);
        $context = $this->buildRequestContext($this->cert_path);
        $fp = stream_socket_client($remote, $err, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
        if ($fp) {
            $req = '';
            $req .= "POST {$this->end_point} HTTP/1.1\r\n";
            $req .= "Host: {$this->host}\r\n";
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

    /**
     * @param $cert_path
     * @return resource
     */
    private function buildRequestContext($cert_path)
    {
        $context = stream_context_create();
        $result = stream_context_set_option($context, 'ssl', 'verify_host', true);
        if (!empty($cert_path)) {
            $result = stream_context_set_option($context, 'ssl', 'cafile', $cert_path);
            $result = stream_context_set_option($context, 'ssl', 'verify_peer', true);
            return $context;
        } else {
            $result = stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
            return $context;
        }
    }

    /**
     * @param $host
     * @param $opts
     * @return string
     */
    private function buildRemotePath($host, $opts)
    {
        $transport = '';
        $port = 80;
        if (!empty($opts['transport'])) {
            $transport = $opts['transport'];
        }
        if (!empty($opts['port'])) {
            $port = $opts['port'];
        }
        $remote = $transport . '://' . $host . ':' . $port;
        return $remote;
    }
} 