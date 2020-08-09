<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\Strategy;

use Bizkit\VersioningBundle\Exception\InvalidVersionFormatException;
use Bizkit\VersioningBundle\Strategy\IncrementingStrategy;
use Bizkit\VersioningBundle\Tests\TestCase;
use Bizkit\VersioningBundle\Version;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * @covers \Bizkit\VersioningBundle\Strategy\IncrementingStrategy
 */
final class IncrementingStrategyTest extends TestCase
{
    /**
     * @var IncrementingStrategy|null
     */
    private $strategy;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|StyleInterface|null
     */
    private $io;

    protected function setUp(): void
    {
        $this->strategy = new IncrementingStrategy();
        $this->io = $this->createMock(StyleInterface::class);
    }

    protected function tearDown(): void
    {
        $this->strategy = null;
        $this->io = null;
    }

    /**
     * @dataProvider validVersionAndIncrementedVersionPairs
     */
    public function testVersionIsIncremented(string $version, string $incrementedVersion): void
    {
        $oldVersion = new Version($version, new \DateTimeImmutable('2005-05-05'));

        $newVersion = ($this->strategy)($this->io, $oldVersion);

        $this->assertSame($incrementedVersion, $newVersion->getVersionNumber());
        $this->assertNotSame(
            $oldVersion->getReleaseDate()->format(\DateTimeInterface::RFC3339),
            $newVersion->getReleaseDate()->format(\DateTimeInterface::RFC3339)
        );
    }

    /**
     * @dataProvider initialValues
     */
    public function testInitialVersionIsReturnedWhenNullIsPassed(string $initialValue): void
    {
        $newVersion = ($this->strategy)($this->io);

        $this->assertSame($initialValue, $newVersion->getVersionNumber());
    }

    /**
     * @dataProvider invalidVersions
     */
    public function testExceptionIsThrownOnInvalidVersion(string $invalidVersion): void
    {
        $this->expectException(InvalidVersionFormatException::class);

        ($this->strategy)($this->io, new Version($invalidVersion));
    }

    public function validVersionAndIncrementedVersionPairs(): iterable
    {
        yield ['1', '2'];
        yield ['10', '11'];
    }

    public function initialValues(): iterable
    {
        yield ['1'];
    }

    public function invalidVersions(): iterable
    {
        yield ['1.2.3'];
        yield ['-1'];
        yield ['0'];
    }
}
