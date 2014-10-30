<?php
namespace Raygun4php
{
    class RaygunEnvironmentMessage
    {
        public $UtcOffset = 0;

        public function __construct()
        {
            if (ini_get('date.timezone'))
            {
                $this->UtcOffset = @date('Z') / 3600;
            }

            $this->UtcOffset = max($this->UtcOffset, -24);
            $this->UtcOffset = min($this->UtcOffset, 24);
        }
    }
}
