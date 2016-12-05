<?php
namespace Raygun4php
{
    class RaygunRequestMessage
    {
        public $HostName;
        public $Url;
        public $HttpMethod;
        public $IpAddress;
        public $QueryString;
        public $Headers;
        public $Data;
        public $Form;
        public $RawData;

        public function __construct()
        {
            if (php_sapi_name() !== 'cli') {
                $this->HostName = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : null;
                $this->HttpMethod = (isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : null;
                $this->Url = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] :  null;
                $this->IpAddress = $this->getRemoteAddr();

                if (array_key_exists('QUERY_STRING', $_SERVER))
                {
                  parse_str($_SERVER['QUERY_STRING'], $this->QueryString);

                  if (empty($this->QueryString))
                  {
                      $this->QueryString = null;
                  }
                }
            }

            $this->Headers = $this->emu_getAllHeaders();

            $utf8_convert = function($value) use (&$utf8_convert) {
                return is_array($value) ?
                array_map($utf8_convert, $value) :
                iconv('UTF-8', 'UTF-8//IGNORE', $value);
            };

            $utf8_convert_server = function($value) use (&$utf8_convert_server) {
                return is_array($value) ?
                array_map($utf8_convert_server, $value) :
                iconv('UTF-8', 'UTF-8', utf8_encode($value));
            };

            $this->Form = array_map($utf8_convert, $_POST);

            $this->Data = array_map($utf8_convert_server, $_SERVER);

            if (php_sapi_name() !== 'cli')
            {
                $contentType = null;
                if (isset($_SERVER['CONTENT_TYPE']))
                {
                    $contentType = $_SERVER['CONTENT_TYPE'];
                }
                else if (isset($_SERVER['HTTP_CONTENT_TYPE']))
                {
                    $contentType = $_SERVER['HTTP_CONTENT_TYPE'];
                }

                if ($_SERVER['REQUEST_METHOD'] != 'GET' &&
                    $contentType != null &&
                    $contentType != 'application/x-www-form-urlencoded' &&
                    $contentType != 'multipart/form-data' &&
                    $contentType != 'text/html')
                {
                  $raw = file_get_contents('php://input');

                  if ($raw != null && strlen($raw) > 4096)
                  {
                    $raw = substr($raw, 0, 4095);
                  }

                  $this->RawData = iconv('UTF-8', 'UTF-8//IGNORE', $raw);
                }
            }
        }

        private function emu_getAllHeaders()
        {
            if (!function_exists('getallheaders'))
            {
                $headers = array();
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

        private function getRemoteAddr()
        {
            $ip = null;

            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
              $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            else if (!empty($_SERVER['REMOTE_ADDR']))
            {
              $ip = $_SERVER['REMOTE_ADDR'];
            }

            return $ip;
        }
    }
}
