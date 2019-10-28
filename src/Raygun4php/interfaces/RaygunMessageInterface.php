<?php
namespace Raygun4php\Interfaces;

interface RaygunMessageInterface
{
    public function Build(\Exception $exception): void;

    public function toJson(): string;
}