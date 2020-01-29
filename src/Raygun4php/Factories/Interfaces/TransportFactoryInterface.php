<?php

namespace Raygun4php\Factories\Interfaces;

use Raygun4php\Interfaces\TransportInterface;

interface TransportFactoryInterface
{
    public function build(?float $timeout, $proxy): TransportInterface;
}
