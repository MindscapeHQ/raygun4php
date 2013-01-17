<?php
namespace Raygun4php
{
    require_once realpath(__DIR__.'/RaygunMessageDetails.php');
    require_once realpath(__DIR__.'/RaygunExceptionMessage.php');
    require_once realpath(__DIR__.'/RaygunRequestMessage.php');
    require_once realpath(__DIR__.'/RaygunClientMessage.php');

    class RaygunMessage
    {
        protected $occurredOn;
        public $details;

        public function __construct()
        {
            $this->occurredOn = gmdate("M d Y H:i:s");
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
