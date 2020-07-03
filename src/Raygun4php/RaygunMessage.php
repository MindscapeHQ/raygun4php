<?php

namespace Raygun4php;

use Raygun4php\Interfaces\RaygunMessageInterface;

class RaygunMessage implements RaygunMessageInterface
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

    /**
     * @param \Throwable $exception
     */
    public function build(\Throwable $exception): void
    {
        $this->Details->MachineName = gethostname();
        $this->Details->Error = new RaygunExceptionMessage($exception);
        $this->Details->Request = new RaygunRequestMessage();
        $this->Details->Environment = new RaygunEnvironmentMessage();
        $this->Details->Client = new RaygunClientMessage();
    }

    private function toJsonRemoveUnicodeSequences($struct)
    {
        return preg_replace_callback("/\\\\u([a-f0-9]{4})/", function ($matches) {
            $hex = 'U' . $matches[1];
            if (!ctype_xdigit($hex)) {
                // Candidate is not a hexadecimal string, skip
                return null;
            }
            return iconv('UCS-4LE', 'UTF-8', pack('V', hexdec($hex)));
        }, json_encode($struct));
    }

    private function removeNullBytes($string)
    {
        return str_replace("\0", '', $string);
    }

    /**
     * Returns the JSON representation of the message object
     *
     * @return string
     */
    public function toJson(): string
    {
        $json = $this->toJsonRemoveUnicodeSequences($this);
        return $this->removeNullBytes($json);
    }
}
