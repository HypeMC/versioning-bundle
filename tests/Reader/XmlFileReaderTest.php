<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\Reader;

use Bizkit\VersioningBundle\Exception\InvalidDataException;
use Bizkit\VersioningBundle\Reader\XmlFileReader;
use Bizkit\VersioningBundle\Tests\TestCase;

/**
 * @covers \Bizkit\VersioningBundle\Reader\XmlFileReader
 */
final class XmlFileReaderTest extends TestCase
{
    public function testValidData(): void
    {
        $reader = new XmlFileReader(__DIR__.'/Fixtures/xml/valid.xml', 'app');

        $version = $reader->read();

        $this->assertSame('1.2.3', $version->getVersionNumber());
        $this->assertSame('2020-05-22T11:58:13+02:00', $version->getReleaseDate()->format(\DateTimeInterface::RFC3339));
    }

    public function testExceptionIsThrownOnInvalidPrefix(): void
    {
        $file = __DIR__.'/Fixtures/xml/valid.xml';

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage(sprintf('Invalid XML structure for "%s".', $file));

        (new XmlFileReader($file, 'application'))->read();
    }

    public function testExceptionIsThrownIfDataIsNotValidXml(): void
    {
        $file = __DIR__.'/Fixtures/xml/invalid-xml.xml';

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage(sprintf('Invalid XML in file "%s":', $file));

        (new XmlFileReader($file, 'application'))->read();
    }

    public function testExceptionIsThrownIfParametersIsNotADOMElement(): void
    {
        $file = __DIR__.'/Fixtures/xml/invalid-structure-parameters.xml';

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage(sprintf('Invalid XML structure for "%s", missing "parameters" key.', $file));

        (new XmlFileReader($file, 'application'))->read();
    }

    public function testExceptionIsThrownIfParameterIsNotArray(): void
    {
        $file = __DIR__.'/Fixtures/xml/invalid-not-array.xml';

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage(sprintf('Invalid XML structure for "%s", missing "parameter" keys.', $file));

        (new XmlFileReader($file, 'application'))->read();
    }

    public function testExceptionIsThrownOnInvalidStructure(): void
    {
        $file = __DIR__.'/Fixtures/xml/invalid-structure.xml';

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage(sprintf('Invalid XML structure for "%s".', $file));

        (new XmlFileReader($file, 'application'))->read();
    }

    public function testExceptionIsThrownOnInvalidDate(): void
    {
        $file = __DIR__.'/Fixtures/xml/invalid-date.xml';

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage(sprintf('Invalid release date in file "%s": ', $file));

        (new XmlFileReader($file, 'application'))->read();
    }
}
