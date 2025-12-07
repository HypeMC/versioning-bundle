<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\Strategy;

use Bizkit\VersioningBundle\Exception\InvalidVersionFormatException;
use Bizkit\VersioningBundle\Strategy\SemVerStrategy;
use Bizkit\VersioningBundle\Tests\TestCase;
use Bizkit\VersioningBundle\Version;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * @covers \Bizkit\VersioningBundle\Strategy\SemVerStrategy
 */
final class SemVerStrategyTest extends TestCase
{
    private SemVerStrategy $strategy;
    private MockObject&StyleInterface $io;

    protected function setUp(): void
    {
        $this->strategy = new SemVerStrategy('minor');
        $this->io = $this->createMock(StyleInterface::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->strategy,
            $this->io,
        );
    }

    /**
     * @dataProvider validVersionAndIncrementedVersionPairs
     */
    public function testVersionIsIncremented(string $version, string $incrementedVersion, string $type): void
    {
        $this->io->method('choice')->willReturn($type);

        $oldVersion = new Version($version, new \DateTimeImmutable('2005-05-05'));

        $newVersion = ($this->strategy)($this->io, $oldVersion);

        self::assertSame($incrementedVersion, $newVersion->getVersionNumber());
        self::assertNotSame(
            $oldVersion->getReleaseDate()->format(\DateTimeInterface::RFC3339),
            $newVersion->getReleaseDate()->format(\DateTimeInterface::RFC3339),
        );
    }

    /**
     * @dataProvider validVersionAndIncrementedVersionPairs
     */
    public function testDefaultVersionType(string $version, string $incrementedVersion, string $type): void
    {
        $this->io
            ->method('choice')
            ->with(self::isType('string'), self::isType('array'), $type)
            ->willReturn($type)
        ;

        $newVersion = (new SemVerStrategy($type))($this->io, new Version($version));

        self::assertSame($incrementedVersion, $newVersion->getVersionNumber());
    }

    public function testExceptionIsThrownOnInvalidDefaultVersionType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "foo" given, expected one of: "major", "minor", "patch".');

        new SemVerStrategy('foo');
    }

    public static function validVersionAndIncrementedVersionPairs(): iterable
    {
        yield ['1.2.3', '2.0.0', 'major'];
        yield ['1.2.3', '1.3.0', 'minor'];
        yield ['1.2.3', '1.2.4', 'patch'];
    }

    /**
     * @dataProvider initialValues
     */
    public function testInitialVersionIsReturnedWhenNullIsPassed(string $initialValue, string $type): void
    {
        $this->io->method('choice')->willReturn($type);

        $newVersion = ($this->strategy)($this->io);

        self::assertSame($initialValue, $newVersion->getVersionNumber());
    }

    public static function initialValues(): iterable
    {
        yield ['1.0.0', 'major'];
        yield ['0.1.0', 'minor'];
        yield ['0.0.1', 'patch'];
    }

    /**
     * @dataProvider invalidVersions
     */
    public function testExceptionIsThrownOnInvalidVersion(string $invalidVersion, string $type): void
    {
        $this->io->method('choice')->willReturn($type);

        $this->expectException(InvalidVersionFormatException::class);

        ($this->strategy)($this->io, new Version($invalidVersion));
    }

    public static function invalidVersions(): iterable
    {
        yield ['1', 'major'];
        yield ['0.1', 'minor'];
        yield ['0.0.1.2', 'patch'];
    }
}
