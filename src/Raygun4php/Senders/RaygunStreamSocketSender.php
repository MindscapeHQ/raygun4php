<?php

namespace Raygun4php\Senders;


class RaygunStreamSocketSender {

    private $apiKey;

    function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }


    public  function postAsync(
        $host,
        $path,
        $data_to_send,
        $cert_path,
        $opts = array('headers' => 0, 'transport' => 'ssl', 'port' => 443)
    ) {
        $remote = $this->buildRemotePath($host, $opts);
        $context = $this->buildRequestContext($cert_path);
        $connectionFlags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT;
        $fp = stream_socket_client($remote, $errorNumber, $errorString, 10, $connectionFlags, $context);
        stream_set_blocking($fp, 0);

        if ($fp) {
            $req = $this->buildRequestBody($host, $path, $data_to_send);
            fwrite($fp, $req);
            fwrite($fp, $data_to_send);
            fclose($fp);
            return 202;
        } else {
            syslog(LOG_WARNING, "Error logging error with raygun: " . $errorString);
            return null;
        }
    }

    /**
     * @param $host
     * @param $path
     * @param $data_to_send
     * @return string
     */
    private function buildRequestBody($host, $path, $data_to_send)
    {
        $req = '';
        $req .= "POST $path HTTP/1.1\r\n";
        $req .= "Host: $host\r\n";
        $req .= "X-ApiKey: " . $this->apiKey . "\r\n";
        $req .= 'Content-length: ' . strlen($data_to_send) . "\r\n";
        $req .= "Content-type: application/json\r\n";
        $req .= "Connection: close\r\n\r\n";
        return $req;
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