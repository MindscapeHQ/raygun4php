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
            $this->BuildInternal();
            $this->Details->Error = new RaygunExceptionMessage($exception);
        }

        public function BuildFromRaw($exception, $file, $line, $message, $className)
        {
            $this->BuildInternal();
            $this->Details->Error = RaygunExceptionMessage::ConstructFromRaw($exception, $file, $line, $message, $className);
        }

        private function BuildInternal()
        {
          $this->Details->MachineName = gethostname();
          $this->Details->Request = new RaygunRequestMessage();
          $this->Details->Environment = new RaygunEnvironmentMessage();
          $this->Details->Client = new RaygunClientMessage();
        }
    }
}