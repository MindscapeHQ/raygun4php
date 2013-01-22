<?php
namespace Raygun4php
{
    class RaygunRequestMessage
    {
        public $hostName;
        public $url;
        public $httpMethod;
        public $ipAddress;
        public $queryString;
        public $headers;
        public $data;
        public $statusCode;

        public function __construct()
        {
            $this->hostName = $_SERVER['HTTP_HOST'];
            $this->httpMethod = $_SERVER['REQUEST_METHOD'];
            $this->url = $_SERVER['REQUEST_URI'];

            $ipAddr = $_SERVER['REMOTE_ADDR'];
            if ($ipAddr == "::1")
            {
                $ipAddr = $ipAddr." (IPv6 localhost)";
            }
            $this->ipAddress = $ipAddr;

        }
    }
}
