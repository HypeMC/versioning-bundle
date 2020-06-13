<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Exception;

use Bizkit\VersioningBundle\Version;

class InvalidVersionFormatException extends \InvalidArgumentException
{
    /**
     * @var Version
     */
    private $version;

    public function __construct(Version $version, ?\Throwable $previous = null)
    {
        $this->version = $version;

        parent::__construct(sprintf('Invalid version format "%s".', $version), 0, $previous);
    }

    public function getVersion(): Version
    {
        return $this->version;
    }
}
