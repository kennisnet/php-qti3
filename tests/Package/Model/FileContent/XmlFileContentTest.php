<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\FileContent;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\XmlFileContent;
use App\SharedKernel\Domain\Qti\Shared\Xml\Reader\IXmlReader;
use DOMDocument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class XmlFileContentTest extends TestCase
{
    #[Test]
    public function fromStringCreatesInstanceCorrectly(): void
    {
        $xmlContent = '<root><child>Test</child></root>';
        $xmlDocument = new DOMDocument();
        $xmlDocument->loadXML($xmlContent);

        $xmlReader = $this->createMock(IXmlReader::class);
        $xmlReader
            ->expects($this->once())
            ->method('read')
            ->with($xmlContent)
            ->willReturn($xmlDocument);

        $xmlFileContent = XmlFileContent::fromString($xmlContent, $xmlReader);

        $this->assertInstanceOf(XmlFileContent::class, $xmlFileContent);
        $this->assertSame($xmlDocument, $xmlFileContent->xmlDocument);
    }

    #[Test]
    public function toStringReturnsFormattedXml(): void
    {
        $xmlDocument = new DOMDocument();
        $xmlDocument->loadXML('<root><child>Test</child></root>');

        $xmlFileContent = new XmlFileContent($xmlDocument);

        $expectedXml = <<<XML
<?xml version="1.0"?>
<root>
  <child>Test</child>
</root>

XML;

        $this->assertSame(trim($expectedXml), trim((string) $xmlFileContent));
    }

    #[Test]
    public function toStringThrowsExceptionOnFailure(): void
    {
        $xmlDocument = $this->createMock(DOMDocument::class);
        $xmlDocument
            ->expects($this->once())
            ->method('saveXML')
            ->willReturn(false);

        $xmlFileContent = new XmlFileContent($xmlDocument);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate XML');

        (string) $xmlFileContent;
    }

    #[Test]
    public function aTextWithSpecialCharactersWillBeConvertedToXmlEntities(): void
    {
        $xmlDocument = new DOMDocument();
        $node = $xmlDocument->createElement('test');
        $node->textContent = 'x < y & y > z & "z ≥ w" & \' w ≤ x \'';
        $xmlDocument->append($node);
        $xmlFileContent = new XmlFileContent($xmlDocument);

        $this->assertStringContainsString(
            'x &lt; y &amp; y &gt; z &amp; "z &#x2265; w" &amp; \' w &#x2264; x \'',
            (string) $xmlFileContent
        );
    }
}
