<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Writer;

use Bizkit\VersioningBundle\Version;

final class XmlFileWriter extends AbstractFileWriter
{
    public function write(Version $version): void
    {
        $document = new \DOMDocument('1.0', 'utf-8');
        $document->formatOutput = true;

        $comment = $document->createComment(self::COMMENT);
        $document->appendChild($comment);

        $container = $document->createElementNS('http://symfony.com/schema/dic/services', 'container');
        $container->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $container->setAttribute('xsi:schemaLocation', 'http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd');
        $document->appendChild($container);

        $parameters = $document->createElement('parameters');
        $container->appendChild($parameters);

        $parameters->appendChild($this->createParameter($document, 'version', $version->getVersionNumber()));
        $parameters->appendChild($this->createParameter($document, 'version_hash', $version->getVersionHash()));
        $parameters->appendChild($this->createParameter($document, 'release_date', $version->getReleaseDate()->format(\DateTimeInterface::RFC3339)));

        $this->writeFileContents($document->saveXML());
    }

    private function createParameter(\DOMDocument $document, string $key, string $value): \DOMElement
    {
        $parameter = $document->createElement('parameter');

        $parameter->setAttribute('type', 'string');
        $parameter->setAttribute('key', $this->prefix.'.'.$key);

        $text = $document->createTextNode(str_replace('%', '%%', $value));
        $parameter->appendChild($text);

        return $parameter;
    }
}
