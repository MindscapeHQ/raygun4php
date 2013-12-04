<?php
namespace Raygun4php
{
    class RaygunEnvironmentMessage
    {
        public $utcOffset = 0;

        public function __construct()
        {
            if (ini_get('date.timezone'))
            {
                $this->utcOffset = @date('Z') / 3600;
            }

            $utcOffset = max($utcOffset, -24);
            $utcOffset = min($utcOffset, 24);
        }
    }
}