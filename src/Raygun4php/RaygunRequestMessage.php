<?php
namespace Raygun4php
{
    class RaygunRequestMessage
    {
        public $queryString;
        public $headers;
        public $data;
        public $form;
        public $rawData;

        public function __construct()
        {
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