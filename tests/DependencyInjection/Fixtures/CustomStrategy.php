<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\DependencyInjection\Fixtures;

use Bizkit\VersioningBundle\Strategy\StrategyInterface;
use Bizkit\VersioningBundle\Version;
use Symfony\Component\Console\Style\StyleInterface;

final class CustomStrategy implements StrategyInterface
{
    public function __invoke(StyleInterface $io, ?Version $version = null): Version
    {
        return new Version('foo');
    }
}
