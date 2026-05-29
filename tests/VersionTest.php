<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests;

use Bizkit\VersioningBundle\Version;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Version::class)]
final class VersionTest extends TestCase
{
    public function testPassedValues(): void
    {
        $versionNumber = uniqid('1.2.3-', true);
        $date = new \DateTimeImmutable('2019-03-02T10:56:12+02:00');

        $version = new Version($versionNumber, $date);

        self::assertSame($versionNumber, $version->getVersionNumber());
        self::assertSame(md5($versionNumber), $version->getVersionHash());
        self::assertSame($date, $version->getReleaseDate());
    }

    public function testDefaultDateIsSet(): void
    {
        $version = new Version('1.2.3');

        self::assertInstanceOf(\DateTimeInterface::class, $version->getReleaseDate());
    }

    public function testVersionNumberIsReturnedWhenCastToString(): void
    {
        $versionNumber = uniqid('1.2.3-', true);

        $version = new Version($versionNumber);

        self::assertSame($versionNumber, (string) $version);
    }
}
