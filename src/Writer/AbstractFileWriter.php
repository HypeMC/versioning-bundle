<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Writer;

use Bizkit\VersioningBundle\Exception\StorageException;

abstract class AbstractFileWriter implements WriterInterface
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $prefix;

    final public function __construct(string $file, string $prefix)
    {
        $this->file = $file;
        $this->prefix = $prefix;
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
