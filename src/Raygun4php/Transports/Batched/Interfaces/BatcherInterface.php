<?php

namespace Raygun4php\Transports\Batched\Interfaces;

interface BatcherInterface
{
    /**
     * Store a crash report on disk
     * @param string $messageJson
     * @return bool
     */
    public function add(string $messageJson): bool;

    /**
     * Generate a JSON array of all the stored crash reports
     * @return string
     */
    public function build(): string;

    /**
     * Get the number of crash report files currently on disk
     * @return int
     */
    public function count(): int;
}
