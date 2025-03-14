<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\FileContent;

use App\SharedKernel\Domain\Qti\Shared\Xml\Reader\IXmlReader;
use DOMDocument;
use RuntimeException;

readonly class XmlFileContent implements IMemoryFileContent
{
    public function __construct(
        public DOMDocument $xmlDocument
    ) {}

    public static function fromString(string $content, IXmlReader $xmlReader): self
    {
        $xmlDocument = $xmlReader->read($content);

        return new self($xmlDocument);
    }

    public function __toString(): string
    {
        $this->xmlDocument->preserveWhiteSpace = true;
        $this->xmlDocument->formatOutput = true;

        $xml = $this->xmlDocument->saveXML();
        if ($xml === false) {
            throw new RuntimeException('Failed to generate XML'); // @codeCoverageIgnore
        }

        return $xml;
    }
}
