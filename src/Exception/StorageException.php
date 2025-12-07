<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Exception;

class StorageException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $path,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
