<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Exception;

use Bizkit\VersioningBundle\Version;

class InvalidVersionFormatException extends \InvalidArgumentException
{
    public function __construct(
        private readonly Version $version,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Invalid version format "%s".', $version), 0, $previous);
    }

    public function getVersion(): Version
    {
        return $this->version;
    }
}
