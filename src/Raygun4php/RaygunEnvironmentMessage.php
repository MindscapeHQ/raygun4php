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

            $this->utcOffset = max($this->utcOffset, -24);
            $this->utcOffset = min($this->utcOffset, 24);
        }
    }
}
