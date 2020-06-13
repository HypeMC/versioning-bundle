<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Strategy;

use Bizkit\VersioningBundle\Exception\InvalidVersionFormatException;
use Bizkit\VersioningBundle\Version;
use Symfony\Component\Console\Style\StyleInterface;

interface StrategyInterface
{
    /**
     * @throws InvalidVersionFormatException
     */
    public function __invoke(StyleInterface $io, ?Version $version = null): Version;
}
