<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\Writer;

use Bizkit\VersioningBundle\Tests\TestCase;
use Bizkit\VersioningBundle\Version;
use Bizkit\VersioningBundle\Writer\WriterInterface;
use Bizkit\VersioningBundle\Writer\XmlFileWriter;

/**
 * @covers \Bizkit\VersioningBundle\Writer\XmlFileWriter
 */
final class XmlFileWriterTest extends TestCase
{
    /**
     * @var string|null
     */
    private $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir().'/version.xml';
    }

    protected function tearDown(): void
    {
        unlink($this->file);
        $this->file = null;
    }

    /**
     * @dataProvider prefixes
     */
    public function testWrite(string $prefix): void
    {
        $version = new Version('1.2.3', new \DateTimeImmutable('2019-03-02T10:56:12+02:00'));

        $writer = new XmlFileWriter($this->file, $prefix);
        $writer->write($version);

        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<!--%s-->
<container xmlns="http://symfony.com/schema/dic/services" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd">
  <parameters>
    <parameter type="string" key="%2$s.version">1.2.3</parameter>
    <parameter type="string" key="%2$s.version_hash">b0e8daa258acbb6fc4c86f89e0c9183e</parameter>
    <parameter type="string" key="%2$s.release_date">2019-03-02T10:56:12+02:00</parameter>
  </parameters>
</container>

XML;

        $this->assertStringEqualsFile($this->file, sprintf($xml, WriterInterface::COMMENT, $prefix));
    }

    public function prefixes(): iterable
    {
        yield ['app'];
        yield ['foo'];
    }
}
