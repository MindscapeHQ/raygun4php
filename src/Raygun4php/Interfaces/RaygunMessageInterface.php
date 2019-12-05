<?php

namespace Raygun4php\Interfaces;

interface RaygunMessageInterface
{
    public function build(\Throwable $exception): void;

    public function toJson(): string;
}
