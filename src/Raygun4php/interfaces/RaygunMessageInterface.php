<?php

namespace Raygun4php\Interfaces;

interface RaygunMessageInterface
{
    public function build(\Exception $exception): void;

    public function toJson(): string;
}
