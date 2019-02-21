<?php
namespace Raygun4php
{
    class RaygunClientMessage
    {
        public $Name;
        public $Version;
        public $ClientUrl;

        public function __construct()
        {
            $this->Name = "Raygun4php";
            $this->Version = "1.8.3";
            $this->ClientUrl = "https://github.com/MindscapeHQ/raygun4php";
        }
    }
}
