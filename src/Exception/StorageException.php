<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Exception;

class StorageException extends \RuntimeException
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $message, string $path, ?\Throwable $previous = null)
    {
        $this->path = $path;

        parent::__construct($message, 0, $previous);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
