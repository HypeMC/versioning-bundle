<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\DependencyInjection\Fixtures;

use Bizkit\VersioningBundle\VCS\VCSHandlerInterface;
use Bizkit\VersioningBundle\Version;
use Symfony\Component\Console\Style\StyleInterface;

final class CustomVCSHandler implements VCSHandlerInterface
{
    public function commit(StyleInterface $io, Version $version): void
    {
    }

    public function tag(StyleInterface $io, Version $version): void
    {
    }
}
