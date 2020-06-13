<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\Reader;

use Bizkit\VersioningBundle\Exception\StorageException;
use Bizkit\VersioningBundle\Reader\AbstractFileReader;
use Bizkit\VersioningBundle\Tests\TestCase;
use Bizkit\VersioningBundle\Version;

/**
 * @covers \Bizkit\VersioningBundle\Reader\AbstractFileReader
 */
final class AbstractFileReaderTest extends TestCase
{
    public function testReadFileContents(): void
    {
        $reader = new class(__DIR__.'/Fixtures/file.txt', 'app') extends AbstractFileReader {
            public function read(): Version
            {
                AbstractFileReaderTest::assertSame('foobar', $this->readFileContents());

                return new Version('1', new \DateTimeImmutable());
            }
        };

        $reader->read();
    }

    public function testExceptionIsThrownIfFileDoesNotExist(): void
    {
        $file = __DIR__.'/Fixtures/does-not-exist.txt';

        $reader = new class($file, 'app') extends AbstractFileReader {
            public function read(): Version
            {
                $this->readFileContents();

                return new Version('1', new \DateTimeImmutable());
            }
        };

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(sprintf('Path "%s" does not exist or is not a file.', $file));

        $reader->read();
    }

    public function testExceptionIsThrownIfFileIsNotReadable(): void
    {
        $file = sys_get_temp_dir().'/version.txt';

        touch($file);
        chmod($file, 0);

        try {
            $reader = new class($file, 'app') extends AbstractFileReader {
                public function read(): Version
                {
                    $this->readFileContents();

                    return new Version('1', new \DateTimeImmutable());
                }
            };

            $this->expectException(StorageException::class);
            $this->expectExceptionMessage(sprintf('File "%s" is not readable.', $file));

            $reader->read();
        } finally {
            unlink($file);
        }
    }
}
