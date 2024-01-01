<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\Writer;

use Bizkit\VersioningBundle\Exception\StorageException;
use Bizkit\VersioningBundle\Tests\TestCase;
use Bizkit\VersioningBundle\Version;
use Bizkit\VersioningBundle\Writer\AbstractFileWriter;

/**
 * @covers \Bizkit\VersioningBundle\Writer\AbstractFileWriter
 */
final class AbstractFileWriterTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir().'/version.txt';
    }

    protected function tearDown(): void
    {
        unlink($this->file);
        unset($this->file);
    }

    public function testWriteFileContents(): void
    {
        $writer = new class($this->file, 'app') extends AbstractFileWriter {
            public function write(Version $version): void
            {
                $this->writeFileContents($version->getVersionNumber());
            }
        };

        $contents = uniqid('foo', true);

        $writer->write(new Version($contents, new \DateTimeImmutable()));

        $this->assertStringEqualsFile($this->file, $contents);
    }

    public function testExceptionIsThrownIfFileIsNotWritable(): void
    {
        touch($this->file);
        chmod($this->file, 0400);

        $writer = new class($this->file, 'app') extends AbstractFileWriter {
            public function write(Version $version): void
            {
                $this->writeFileContents('foo');
            }
        };

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(sprintf('File "%s" is not writable.', $this->file));

        $writer->write(new Version('1', new \DateTimeImmutable()));
    }
}
