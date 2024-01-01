<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle;

final class Version
{
    private readonly string $versionHash;

    public function __construct(
        private readonly string $versionNumber,
        private readonly \DateTimeInterface $releaseDate = new \DateTimeImmutable(),
    ) {
        $this->versionHash = md5($versionNumber);
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
