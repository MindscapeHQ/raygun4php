<?php

namespace Raygun4php\Factories\Interfaces;

use Psr\Log\LoggerInterface;
use Raygun4php\Factories\RaygunClientFactory;
use Raygun4php\RaygunClient;

interface RaygunClientFactoryInterface
{
    public function setLogger(LoggerInterface $logger): RaygunClientFactory;
    public function setProxy(string $proxy): RaygunClientFactory;
    public function setTimeout(float $timeout): RaygunClientFactory;

    public function build(): RaygunClient;
}
