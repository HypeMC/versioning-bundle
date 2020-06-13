<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Exception;

class InvalidDataException extends \RuntimeException
{
    private $data;

    public function __construct(string $message, $data, ?\Throwable $previous = null)
    {
        $this->data = $data;

        parent::__construct($message, 0, $previous);
    }

    public function getData()
    {
        return $this->data;
    }
}
