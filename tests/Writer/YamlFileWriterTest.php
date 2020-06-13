<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\Writer;

use Bizkit\VersioningBundle\Tests\TestCase;
use Bizkit\VersioningBundle\Version;
use Bizkit\VersioningBundle\Writer\WriterInterface;
use Bizkit\VersioningBundle\Writer\YamlFileWriter;

/**
 * @covers \Bizkit\VersioningBundle\Writer\YamlFileWriter
 */
final class YamlFileWriterTest extends TestCase
{
    /**
     * @var string
     */
    private $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir().'/version.yaml';
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

        $writer = new YamlFileWriter($this->file, $prefix);
        $writer->write($version);

        $yaml = <<<'YAML'
# %s
parameters:
    %2$s.version: 1.2.3
    %2$s.version_hash: b0e8daa258acbb6fc4c86f89e0c9183e
    %2$s.release_date: '2019-03-02T10:56:12+02:00'

YAML;

        $this->assertStringEqualsFile($this->file, sprintf($yaml, WriterInterface::COMMENT, $prefix));
    }

    public function prefixes(): iterable
    {
        yield ['app'];
        yield ['foo'];
    }
}
