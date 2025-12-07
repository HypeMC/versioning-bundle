<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\VCS;

enum TaggingMode: string
{
    case Always = 'always';
    case Ask = 'ask';
    case Never = 'never';
}
