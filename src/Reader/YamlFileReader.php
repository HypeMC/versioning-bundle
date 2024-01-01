<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Reader;

use Bizkit\VersioningBundle\Exception\InvalidDataException;
use Bizkit\VersioningBundle\Version;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class YamlFileReader extends AbstractFileReader
{
    public function read(): Version
    {
        $yaml = $this->readFileContents();

        try {
            $data = Yaml::parse($yaml);
        } catch (ParseException $e) {
            throw new InvalidDataException(sprintf('Invalid YAML in file "%s": %s.', $this->file, $e->getMessage()), $yaml, $e);
        }

        if (!\is_array($data)) {
            throw new InvalidDataException(
                sprintf('YAML content was expected to decode to an array, "%s" returned for "%s".', \gettype($data), $this->file),
                $data,
            );
        }

        if (!isset($data['parameters'][$this->prefix.'.version'], $data['parameters'][$this->prefix.'.release_date'])) {
            throw new InvalidDataException(sprintf('Invalid YAML structure for "%s".', $this->file), $data);
        }

        try {
            $releaseDate = new \DateTimeImmutable($data['parameters'][$this->prefix.'.release_date']);
        } catch (\Exception $e) {
            throw new InvalidDataException(sprintf('Invalid release date in file "%s": %s.', $this->file, $e->getMessage()), $data, $e);
        }

        return new Version($data['parameters'][$this->prefix.'.version'], $releaseDate);
    }
}
