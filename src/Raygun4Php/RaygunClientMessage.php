<?php
namespace Raygun4Php;

class RaygunClientMessage
{
    public $Name;
    public $Version;
    public $ClientUrl;

    public function __construct()
    {
        $this->Name = "Raygun4Php";
        $this->Version = "1.6.0";
        $this->ClientUrl = "https://github.com/MindscapeHQ/raygun4php";
    }
}
