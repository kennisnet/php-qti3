<?php

declare(strict_types=1);

namespace Qti3\Package\Model\PackageFile;

use Qti3\Package\Model\FileContent\IFileContent;
use Qti3\Package\Model\FileContent\MemoryFileContent;
use Qti3\Shared\Xml\Reader\IXmlReader;
use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use RuntimeException;
use Stringable;

class XmlFile extends PackageFile implements Stringable
{
    private ?DOMDocument $xmlDocument = null;

    public function __construct(
        string $name,
        IFileContent $content,
        private readonly IXmlReader $xmlReader,
        bool $modified = true,
    ) {
        if (!str_ends_with($name, '.xml')) {
            throw new InvalidArgumentException('XML file name must end with .xml');
        }
        parent::__construct($name, $content, false, $modified);
    }

    public function getXml(): DOMDocument
    {
        if (!$this->xmlDocument) {
            $this->xmlDocument = $this->xmlReader->read($this->content->getContent());
            $this->xmlDocument->preserveWhiteSpace = true;
            $this->xmlDocument->formatOutput = true;
            // Once the DOM is materialized, getContent() re-serializes from it
            // and the caller may mutate it in place. We cannot cheaply prove the
            // output still matches the source bytes, so treat the file as
            // modified rather than risk skipping a changed file on write.
            $this->markModified();
        }
        return $this->xmlDocument;
    }

    public function replaceContent(string $xml): void
    {
        $this->xmlDocument = $this->xmlReader->read($xml);
        $this->markModified();
    }

    public function getContent(): IFileContent
    {
        if ($this->xmlDocument === null) {
            return parent::getContent();
        }

        $xml = $this->xmlDocument->saveXML();
        if ($xml === false) {
            throw new RuntimeException(sprintf('Failed to serialize XML file %s', $this->getFilepath())); // @codeCoverageIgnore
        }

        return new MemoryFileContent($xml);
    }

    public function getDocumentElement(): DOMElement
    {
        $documentElement = $this->getXml()->documentElement;
        if (!$documentElement) {
            throw new RuntimeException('Invalid XML document'); // @codeCoverageIgnore
        }

        return $documentElement;
    }

    public function __toString(): string
    {
        return $this->getContent()->getContent();
    }
}
