<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Strategy;

use Bizkit\VersioningBundle\Exception\InvalidVersionFormatException;
use Bizkit\VersioningBundle\Version;
use Symfony\Component\Console\Style\StyleInterface;

final class SemVerStrategy implements StrategyInterface
{
    private const INITIAL_VALUES = [
        'major' => '1.0.0',
        'minor' => '0.1.0',
        'patch' => '0.0.1',
    ];
    private const REGEX = '~^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)$~';

    public function __construct(
        private readonly string $defaultType,
    ) {
        if (!isset(self::INITIAL_VALUES[$defaultType])) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid value "%s" given, expected one of: "%s".',
                $defaultType,
                implode('", "', array_keys(self::INITIAL_VALUES)),
            ));
        }
    }

    public function __invoke(StyleInterface $io, ?Version $version = null): Version
    {
        $availableTypes = array_keys(self::INITIAL_VALUES);

        $selectedType = $io->choice('Which version should be incremented?', $availableTypes, $this->defaultType);

        if (null === $version) {
            return new Version(self::INITIAL_VALUES[$selectedType]);
        }

        $versionNumber = $version->getVersionNumber();

        if (!preg_match(self::REGEX, $versionNumber, $parts)) {
            throw new InvalidVersionFormatException($version);
        }

        $updatedParts = [];
        $setToZero = false;
        foreach ($availableTypes as $type) {
            if ($selectedType === $type) {
                ++$parts[$type];
                $setToZero = true;
            } elseif ($setToZero) {
                $parts[$type] = 0;
            }
            $updatedParts[$type] = $parts[$type];
        }

        return new Version(implode('.', $updatedParts));
    }
}
