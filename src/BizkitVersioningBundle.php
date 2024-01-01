<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class BizkitVersioningBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
