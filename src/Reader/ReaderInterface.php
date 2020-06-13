<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Reader;

use Bizkit\VersioningBundle\Version;

interface ReaderInterface
{
    public function read(): Version;
}
