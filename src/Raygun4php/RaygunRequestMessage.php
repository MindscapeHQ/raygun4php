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
            if (php_sapi_name() !== 'cli') {
                $this->hostName = $_SERVER['HTTP_HOST'];
                $this->httpMethod = $_SERVER['REQUEST_METHOD'];
                $this->url = $_SERVER['REQUEST_URI'];
                $this->ipAddress = $_SERVER['REMOTE_ADDR'];

                parse_str($_SERVER['QUERY_STRING'], $this->queryString);
                if (empty($this->queryString))
                {
                    $this->queryString = null;
                }
            }

            $this->headers = $this->emu_getAllHeaders();
            $this->data = $_SERVER;
            $this->form = array_map(
                function($str) {
                    return iconv('UTF-8', 'UTF-8//IGNORE', $str);
                }, 
                $_POST
            );

            if (php_sapi_name() !== 'cli' &&
                $_SERVER['REQUEST_METHOD'] != 'GET' &&
                $_SERVER['CONTENT_TYPE'] != 'application/x-www-form-urlencoded' &&
                $_SERVER['CONTENT_TYPE'] != 'multipart/form-data' &&
                $_SERVER['CONTENT_TYPE'] != 'text/html')
            {                
                $this->rawData = iconv('UTF-8', 'UTF-8//IGNORE', file_get_contents('php://input'));
            }
        }

        private function emu_getAllHeaders()
        {
            if (!function_exists('getallheaders'))
            {
                $headers = '';
                foreach ($_SERVER as $name => $value)
                {
                    if (substr($name, 0, 5) == 'HTTP_')
                    {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }
            else
            {
                return getallheaders();
            }
        }
    }
}