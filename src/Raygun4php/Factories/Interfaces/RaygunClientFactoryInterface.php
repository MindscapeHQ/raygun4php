<?php

namespace Raygun4php\Factories\Interfaces;

use Psr\Log\LoggerInterface;
use Raygun4php\Factories\RaygunClientFactory;
use Raygun4php\RaygunClient;

interface RaygunClientFactoryInterface
{
    public function setLogger(LoggerInterface $logger): RaygunClientFactory;

    public function build(): RaygunClient;
}
