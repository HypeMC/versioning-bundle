<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Exception;

class InvalidDataException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly mixed $data,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
