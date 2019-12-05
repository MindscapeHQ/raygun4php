<?php

namespace Raygun4php\Tests\Stubs;

use Exception;
use Raygun4php\Interfaces\TransportInterface;
use Raygun4php\Interfaces\RaygunMessageInterface;

class TransportGetMessageStub implements TransportInterface
{
    protected $messageIsSet = false;
    protected $message;

    public function transmit(RaygunMessageInterface $message): bool
    {
        $this->message = $message;
        $this->messageIsSet = true;
        return true;
    }

    public function getMessage(): RaygunMessageInterface
    {
        if (!$this->messageIsSet) {
            throw new Exception('No message available..');
        };
        return $this->message;
    }
}
