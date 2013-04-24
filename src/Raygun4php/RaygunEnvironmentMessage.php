<?php
namespace Raygun4php
{
    class RaygunEnvironmentMessage
    {
        public $utcOffset;

        public function __construct()
        {
            if (ini_get('date.timezone'))
            {
                $this->utcOffset = @date('Z') / 3600;
            }
        }
    }
}