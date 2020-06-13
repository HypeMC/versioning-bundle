<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle;

final class Version
{
    /**
     * @var string
     */
    private $versionNumber;

    /**
     * @var string
     */
    private $versionHash;

    /**
     * @var \DateTimeInterface
     */
    private $releaseDate;

    public function __construct(string $versionNumber, ?\DateTimeInterface $releaseDate = null)
    {
        $this->versionNumber = $versionNumber;
        $this->versionHash = md5($versionNumber);
        $this->releaseDate = $releaseDate ?? new \DateTimeImmutable();
    }

    public function getVersionNumber(): string
    {
        return $this->versionNumber;
    }

    public function getVersionHash(): string
    {
        return $this->versionHash;
    }

    public function getReleaseDate(): \DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function __toString(): string
    {
        return $this->versionNumber;
    }
}
