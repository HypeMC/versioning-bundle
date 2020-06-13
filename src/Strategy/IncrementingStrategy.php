<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Strategy;

use Bizkit\VersioningBundle\Exception\InvalidVersionFormatException;
use Bizkit\VersioningBundle\Version;
use Symfony\Component\Console\Style\StyleInterface;

final class IncrementingStrategy implements StrategyInterface
{
    private const INITIAL_VALUE = '1';
    private const REGEX = '~^[1-9]\d*$~';

    public function __invoke(StyleInterface $io, ?Version $version = null): Version
    {
        if (null === $version) {
            return new Version(self::INITIAL_VALUE);
        }

        $versionNumber = $version->getVersionNumber();

        if (!preg_match(self::REGEX, $versionNumber)) {
            throw new InvalidVersionFormatException($version);
        }

        return new Version((string) ++$versionNumber);
    }
}
