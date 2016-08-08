<?php
namespace Raygun4php
{
    require_once realpath(__DIR__.'/RaygunMessageDetails.php');
    require_once realpath(__DIR__.'/RaygunExceptionMessage.php');
    require_once realpath(__DIR__.'/RaygunRequestMessage.php');
    require_once realpath(__DIR__.'/RaygunEnvironmentMessage.php');
    require_once realpath(__DIR__.'/RaygunClientMessage.php');

    class RaygunMessage
    {
        public $OccurredOn;
        public $Details;

        public function __construct($timestamp = null)
        {
            if ($timestamp === null) {
                $timestamp = time();
            }
            $this->OccurredOn = gmdate("Y-m-d\TH:i:s\Z", $timestamp);
            $this->Details = new RaygunMessageDetails();
        }

        public function Build($exception)
        {
            $this->Details->MachineName = gethostname();
            $this->Details->Error = new RaygunExceptionMessage($exception);
            $this->Details->Request = new RaygunRequestMessage();
            $this->Details->Environment = new RaygunEnvironmentMessage();
            $this->Details->Client = new RaygunClientMessage();
        }
    }
}
