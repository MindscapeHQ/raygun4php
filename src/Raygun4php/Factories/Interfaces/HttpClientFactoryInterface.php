<?php

namespace Raygun4php\Factories\Interfaces;

use GuzzleHttp\ClientInterface;

interface HttpClientFactoryInterface
{
    public function build(?float $timeout, string $proxy): ClientInterface;
}
