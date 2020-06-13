<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Reader;

use Bizkit\VersioningBundle\Exception\InvalidDataException;
use Bizkit\VersioningBundle\Version;
use Symfony\Component\Config\Util\Exception\XmlParsingException;
use Symfony\Component\Config\Util\XmlUtils;

final class XmlFileReader extends AbstractFileReader
{
    public function read(): Version
    {
        $xml = $this->readFileContents();

        try {
            $document = XmlUtils::parse($xml);
        } catch (XmlParsingException $e) {
            throw new InvalidDataException(sprintf('Invalid XML in file "%s": %s.', $this->file, $e->getMessage()), $xml, $e);
        }

        $parameters = $document->getElementsByTagName('parameters')->item(0);

        if (!$parameters instanceof \DOMElement) {
            throw new InvalidDataException(sprintf('Invalid XML structure for "%s", missing "parameters" key.', $this->file), $parameters);
        }

        $data = XmlUtils::convertDomElementToArray($parameters);

        if (null === $data || !isset($data['parameter']) || !\is_array($data['parameter'])) {
            throw new InvalidDataException(sprintf('Invalid XML structure for "%s", missing "parameter" keys.', $this->file), $data);
        }

        $data = array_column($data['parameter'], 'value', 'key');

        if (!isset($data[$this->prefix.'.version'], $data[$this->prefix.'.release_date'])) {
            throw new InvalidDataException(sprintf('Invalid XML structure for "%s".', $this->file), $data);
        }

        try {
            $releaseDate = new \DateTimeImmutable($data[$this->prefix.'.release_date']);
        } catch (\Exception $e) {
            throw new InvalidDataException(sprintf('Invalid release date in file "%s": %s.', $this->file, $e->getMessage()), $data, $e);
        }

        return new Version($data[$this->prefix.'.version'], $releaseDate);
    }
}
