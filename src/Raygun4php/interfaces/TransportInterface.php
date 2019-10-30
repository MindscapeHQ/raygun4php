<?php

namespace Raygun4php\Interfaces;

interface TransportInterface
{
    public function transmit(RaygunMessageInterface $message): bool;
}
