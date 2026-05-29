<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\Strategy;

use Bizkit\VersioningBundle\Exception\InvalidVersionFormatException;
use Bizkit\VersioningBundle\Strategy\IncrementingStrategy;
use Bizkit\VersioningBundle\Tests\TestCase;
use Bizkit\VersioningBundle\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Console\Style\StyleInterface;

#[CoversClass(IncrementingStrategy::class)]
final class IncrementingStrategyTest extends TestCase
{
    private IncrementingStrategy $strategy;
    private Stub&StyleInterface $io;

    protected function setUp(): void
    {
        $this->strategy = new IncrementingStrategy();
        $this->io = self::createStub(StyleInterface::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->strategy,
            $this->io,
        );
    }

    #[DataProvider('validVersionAndIncrementedVersionPairs')]
    public function testVersionIsIncremented(string $version, string $incrementedVersion): void
    {
        $oldVersion = new Version($version, new \DateTimeImmutable('2005-05-05'));

        $newVersion = ($this->strategy)($this->io, $oldVersion);

        self::assertSame($incrementedVersion, $newVersion->getVersionNumber());
        self::assertNotSame(
            $oldVersion->getReleaseDate()->format(\DateTimeInterface::RFC3339),
            $newVersion->getReleaseDate()->format(\DateTimeInterface::RFC3339),
        );
    }

    #[DataProvider('initialValues')]
    public function testInitialVersionIsReturnedWhenNullIsPassed(string $initialValue): void
    {
        $newVersion = ($this->strategy)($this->io);

        self::assertSame($initialValue, $newVersion->getVersionNumber());
    }

    #[DataProvider('invalidVersions')]
    public function testExceptionIsThrownOnInvalidVersion(string $invalidVersion): void
    {
        $this->expectException(InvalidVersionFormatException::class);

        ($this->strategy)($this->io, new Version($invalidVersion));
    }

    public static function validVersionAndIncrementedVersionPairs(): iterable
    {
        yield ['1', '2'];
        yield ['10', '11'];
    }

    public static function initialValues(): iterable
    {
        yield ['1'];
    }

    public static function invalidVersions(): iterable
    {
        yield ['1.2.3'];
        yield ['-1'];
        yield ['0'];
    }
}
