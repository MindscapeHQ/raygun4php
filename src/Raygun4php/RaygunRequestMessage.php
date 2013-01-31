<?php
namespace Raygun4php
{
    class RaygunRequestMessage
    {
        public $hostName;
        public $url;
        public $httpMethod;
        public $ipAddress;
        //
        public $queryString;
        public $headers;
        public $data;
        public $form;
        public $rawData;

        public function __construct()
        {
            $this->hostName = $_SERVER['HTTP_HOST'];
            $this->httpMethod = $_SERVER['REQUEST_METHOD'];
            $this->url = $_SERVER['REQUEST_URI'];
            $this->ipAddress = $_SERVER['REMOTE_ADDR'];

            $this->queryString = $_SERVER['QUERY_STRING'];
            $this->headers = getallheaders();
            $this->data = $_SERVER;
            $this->form = $_POST;

            if ($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['CONTENT_TYPE'] != 'application/x-www-form-urlencoded' &&
                $_SERVER['CONTENT_TYPE'] != 'text/html')
            {
                $this->rawData = http_get_request_body();
            }
        }
    }
}