<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests;

use Bizkit\VersioningBundle\Version;

/**
 * @covers \Bizkit\VersioningBundle\Version
 */
final class VersionTest extends TestCase
{
    public function testPassedValues(): void
    {
        $versionNumber = uniqid('1.2.3-', true);
        $date = new \DateTimeImmutable('2019-03-02T10:56:12+02:00');

        $version = new Version($versionNumber, $date);

        $this->assertSame($versionNumber, $version->getVersionNumber());
        $this->assertSame(md5($versionNumber), $version->getVersionHash());
        $this->assertSame($date, $version->getReleaseDate());
    }

    public function testDefaultDateIsSet(): void
    {
        $version = new Version('1.2.3');

        $refProperty = (new \ReflectionObject($version))->getProperty('releaseDate');
        $refProperty->setAccessible(true);

        $this->assertInstanceOf(\DateTimeInterface::class, $refProperty->getValue($version));
    }

    public function testVersionNumberIsReturnedWhenCastToString(): void
    {
        $versionNumber = uniqid('1.2.3-', true);

        $version = new Version($versionNumber);

        $this->assertSame($versionNumber, (string) $version);
    }
}
