<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\VCS;

use Bizkit\VersioningBundle\Version;
use Symfony\Component\Console\Style\StyleInterface;

interface VCSHandlerInterface
{
    public function commit(StyleInterface $io, Version $version): void;

    public function tag(StyleInterface $io, Version $version): void;
}
