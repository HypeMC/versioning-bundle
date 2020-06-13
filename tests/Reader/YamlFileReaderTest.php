<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\Reader;

use Bizkit\VersioningBundle\Exception\InvalidDataException;
use Bizkit\VersioningBundle\Reader\YamlFileReader;
use Bizkit\VersioningBundle\Tests\TestCase;

/**
 * @covers \Bizkit\VersioningBundle\Reader\YamlFileReader
 */
final class YamlFileReaderTest extends TestCase
{
    public function testValidData(): void
    {
        $reader = new YamlFileReader(__DIR__.'/Fixtures/yaml/valid.yaml', 'app');

        $version = $reader->read();

        $this->assertSame('1.2.3', $version->getVersionNumber());
        $this->assertSame('2020-05-22T11:58:13+02:00', $version->getReleaseDate()->format(\DateTimeInterface::RFC3339));
    }

    public function testExceptionIsThrownOnInvalidPrefix(): void
    {
        $file = __DIR__.'/Fixtures/yaml/valid.yaml';

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage(sprintf('Invalid YAML structure for "%s".', $file));

        (new YamlFileReader($file, 'application'))->read();
    }

    public function testExceptionIsThrownIfDataIsNotValidYaml(): void
    {
        $file = __DIR__.'/Fixtures/yaml/invalid-yaml.yaml';

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage(sprintf('Invalid YAML in file "%s":', $file));

        (new YamlFileReader($file, 'application'))->read();
    }

    public function testExceptionIsThrownIfDataIsNotArray(): void
    {
        $file = __DIR__.'/Fixtures/yaml/invalid-not-array.yaml';

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage(sprintf('YAML content was expected to decode to an array, "string" returned for "%s".', $file));

        (new YamlFileReader($file, 'application'))->read();
    }

    public function testExceptionIsThrownOnInvalidStructure(): void
    {
        $file = __DIR__.'/Fixtures/yaml/invalid-structure.yaml';

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage(sprintf('Invalid YAML structure for "%s".', $file));

        (new YamlFileReader($file, 'application'))->read();
    }

    public function testExceptionIsThrownOnInvalidDate(): void
    {
        $file = __DIR__.'/Fixtures/yaml/invalid-date.yaml';

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage(sprintf('Invalid release date in file "%s": ', $file));

        (new YamlFileReader($file, 'application'))->read();
    }
}
