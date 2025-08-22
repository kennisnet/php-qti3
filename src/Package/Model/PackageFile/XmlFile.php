<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\PackageFile;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\IFileContent;
use App\SharedKernel\Domain\Qti\Shared\Xml\Reader\IXmlReader;
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
    ) {
        if (!str_ends_with($name, '.xml')) {
            throw new InvalidArgumentException('XML file name must end with .xml');
        }
        parent::__construct($name, $content);
    }

    public function getXml(): DOMDocument
    {
        if (!$this->xmlDocument) {
            $this->xmlDocument = $this->xmlReader->read($this->content->getContent());
            $this->xmlDocument->preserveWhiteSpace = true;
            $this->xmlDocument->formatOutput = true;
        }
        return $this->xmlDocument;
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
        return $this->content->getContent();
    }
}
