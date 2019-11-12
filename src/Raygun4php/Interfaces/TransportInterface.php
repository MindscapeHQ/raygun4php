<?php

namespace Raygun4php\Interfaces;

/**
 * Interface for implementing a mechanism to transfer a RaygunMessage to the Raygun API.
 */
interface TransportInterface
{
    /**
     * Transmit a RaygunMessage to the Raygun API.
     * Should return true if the transmission attempt is successful.
     * May return false if the delivery of the message is known to have failed.
     *
     * @param RaygunMessageInterface $message
     * @return boolean
     */
    public function transmit(RaygunMessageInterface $message): bool;
}
