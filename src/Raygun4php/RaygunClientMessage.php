<?php
namespace Raygun4php
{
    class RaygunClientMessage
    {
        public $name;
        public $version;
        public $clientUrl;

        public function __construct()
        {
            $this->name = "Raygun4php";
            $this->version = "1.5.2";
            $this->clientUrl = "https://github.com/MindscapeHQ/raygun4php";
        }
    }
}
