<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Writer;

use Bizkit\VersioningBundle\Version;
use Symfony\Component\Yaml\Yaml;

final class YamlFileWriter extends AbstractFileWriter
{
    public function write(Version $version): void
    {
        $yaml = Yaml::dump([
            'parameters' => [
                $this->prefix.'.version' => $version->getVersionNumber(),
                $this->prefix.'.version_hash' => $version->getVersionHash(),
                $this->prefix.'.release_date' => $version->getReleaseDate()->format(\DateTimeInterface::RFC3339),
            ],
        ]);

        $this->writeFileContents(sprintf("# %s\n%s", self::COMMENT, $yaml));
    }
}
