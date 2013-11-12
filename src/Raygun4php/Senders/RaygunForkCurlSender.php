<?php

namespace Raygun4php\Senders;


use Raygun4php\RaygunMessage;

class RaygunForkCurlSender implements RaygunMessageSender
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
        $output = "";
        $cmd = "curl -X POST -H 'Content-Type: application/json' -H 'X-ApiKey: " . $this->apiKey . "'";
        $cmd .= " -d '" . $data_to_send . "' --cacert '" . realpath(__DIR__ . $this->cert_path)
             . "' '" . $this->buildRemotePath($this->host, $this->opts) . $this->end_point . "' > /dev/null 2>&1 &";

        exec($cmd, $output, $exit);
        return $exit;
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