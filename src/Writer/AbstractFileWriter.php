<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Writer;

use Bizkit\VersioningBundle\Exception\StorageException;

abstract class AbstractFileWriter implements WriterInterface
{
    final public function __construct(
        protected readonly string $file,
        protected readonly string $prefix,
    ) {
    }

    protected function writeFileContents(string $contents): void
    {
        if (is_file($this->file) && !is_writable($this->file)) {
            throw new StorageException(sprintf('File "%s" is not writable.', $this->file), $this->file);
        }

        if (false === @file_put_contents($this->file, $contents)) {
            throw new StorageException(sprintf('Failed to write to file "%s".', $this->file), $this->file);
        }
    }
}
