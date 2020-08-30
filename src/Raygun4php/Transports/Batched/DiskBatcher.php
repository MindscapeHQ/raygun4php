<?php

namespace Raygun4php\Transports\Batched;

use Exception;
use FileSystemIterator;
use Raygun4php\Transports\Batched\Interfaces\BatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DiskBatcher implements BatcherInterface
{
    private $logger;
    private $baseDirectory;
    private $raygunFolderName = "raygun4php";
    private $fileFormat = "json";

    public function __construct(string $appIdentifier, string $storeDirectoryPath)
    {
        $this->logger = new NullLogger();
        $this->baseDirectory = "{$storeDirectoryPath}/{$this->raygunFolderName}/{$appIdentifier}";
        $this->ensureBaseDirectoryExists();
    }

    public function add(string $messageJson): bool
    {
        $fileName = $this->baseDirectory . "/" . uniqid() . "." . $this->fileFormat;
        return file_put_contents($fileName, $messageJson);
    }

    public function build(): string
    {
        $concatenatedFiles = [];

        $fsIterator = new FileSystemIterator($this->baseDirectory);

        foreach ($fsIterator as $file) {
            $concatenatedFiles[] = file_get_contents($file->getPathname());
            unlink($file->getPathname());
        }

        $joinedFiles = implode(",", $concatenatedFiles);

        return "[{$joinedFiles}]";
    }

    public function count(): int
    {
        $fsIterator = new FileSystemIterator($this->baseDirectory);
        return iterator_count($fsIterator);
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Attempt to create the base directory where the JSON files will be stored
     *
     * @return bool
     * @throws Exception
     */
    private function ensureBaseDirectoryExists(): bool
    {
        if (!file_exists($this->baseDirectory)) {
            if (!mkdir($this->baseDirectory, 0700, true)) {
                throw new Exception("Directory: [{$this->baseDirectory}] needs to be writable for disk-based batched transport to work");
            }
        }

        return true;
    }
}
