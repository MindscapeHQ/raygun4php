<?php
namespace Raygun4php
{
    require_once realpath(__DIR__.'/RaygunMessageDetails.php');
    require_once realpath(__DIR__.'/RaygunExceptionMessage.php');
    require_once realpath(__DIR__.'/RaygunRequestMessage.php');
    require_once realpath(__DIR__.'/RaygunClientMessage.php');

    class RaygunMessage
    {
        public $occurredOn;
        public $details;

        public function __construct()
        {
            $this->occurredOn = gmdate("Y-m-d\TH:i\Z");
            $this->details = new RaygunMessageDetails();
        }

        public function Build($exception)
        {
            $this->details->machineName = gethostname();
            $this->details->exception = new RaygunExceptionMessage($exception);
            $this->details->request = new RaygunRequestMessage();
            $this->details->client = new RaygunClientMessage();
        }
    }
}
