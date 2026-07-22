<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Model\PackageFile;

use Qti3\Package\Model\FileContent\MemoryFileContent;
use Qti3\Package\Model\PackageFile\XmlFile;
use Qti3\Shared\Xml\Reader\XmlReader;
use DOMDocument;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class XmlFileTest extends TestCase
{
    private MemoryFileContent $content;
    private XmlReader $xmlReader;

    protected function setUp(): void
    {
        $this->content = new MemoryFileContent('content');
        $this->xmlReader = new XmlReader();
    }

    #[Test]
    public function aXmlFilenameCanBeGiven(): void
    {
        $xmlFile = new XmlFile('test.xml', $this->content, $this->xmlReader);

        $this->assertEquals('test.xml', $xmlFile->getFilepath());
    }

    #[Test]
    public function aNonXmlFilenameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new XmlFile('test.tst', $this->content, $this->xmlReader);
    }

    #[Test]
    public function contentIsServedFromTheOriginalBytesWhileTheDomIsNotLoaded(): void
    {
        $xmlFile = new XmlFile('test.xml', new MemoryFileContent('<root/>'), $this->xmlReader);

        $this->assertSame('<root/>', $xmlFile->getContent()->getContent());
    }

    #[Test]
    public function domMutationsSurviveInTheContent(): void
    {
        $xmlFile = new XmlFile('test.xml', new MemoryFileContent('<root/>'), $this->xmlReader);

        $xmlFile->getDocumentElement()->setAttribute('identifier', 'ITEM001');

        $this->assertStringContainsString('<root identifier="ITEM001"/>', $xmlFile->getContent()->getContent());
    }

    #[Test]
    public function replaceContentReplacesTheWholeDocument(): void
    {
        $xmlFile = new XmlFile('test.xml', new MemoryFileContent('<root/>'), $this->xmlReader);

        $xmlFile->replaceContent('<other-root/>');

        $this->assertStringContainsString('<other-root/>', $xmlFile->getContent()->getContent());
        $this->assertStringContainsString('<other-root/>', (string) $xmlFile);
    }

    #[Test]
    public function aFileIsModifiedByDefault(): void
    {
        $xmlFile = new XmlFile('test.xml', new MemoryFileContent('<root/>'), $this->xmlReader);

        $this->assertTrue($xmlFile->isModified());
    }

    #[Test]
    public function aSourcePassthroughStaysUnmodifiedWhileTheDomIsNotLoaded(): void
    {
        $xmlFile = new XmlFile('test.xml', new MemoryFileContent('<root/>'), $this->xmlReader, modified: false);

        // Serving the original bytes without materializing the DOM leaves it
        // unmodified, so a writer may skip it.
        $this->assertSame('<root/>', $xmlFile->getContent()->getContent());
        $this->assertFalse($xmlFile->isModified());
    }

    #[Test]
    public function replaceContentMarksTheFileAsModified(): void
    {
        $xmlFile = new XmlFile('test.xml', new MemoryFileContent('<root/>'), $this->xmlReader, modified: false);

        $xmlFile->replaceContent('<other-root/>');

        $this->assertTrue($xmlFile->isModified());
    }

    #[Test]
    public function materializingTheDomMarksTheFileAsModified(): void
    {
        $xmlFile = new XmlFile('test.xml', new MemoryFileContent('<root/>'), $this->xmlReader, modified: false);

        // Once the DOM is exposed it may be mutated in place, so the file is
        // conservatively treated as modified.
        $xmlFile->getDocumentElement();

        $this->assertTrue($xmlFile->isModified());
    }

    #[Test]
    public function aTextWithSpecialCharactersWillBeConvertedToXmlEntities(): void
    {
        $xmlDocument = new DOMDocument();
        $node = $xmlDocument->createElement('test');
        $node->textContent = 'x < y & y > z & "z ≥ w" & \' w ≤ x \'';
        $xmlDocument->append($node);
        $xmlFile = new XmlFile('test.xml', new MemoryFileContent($xmlDocument->saveXML()), $this->xmlReader);

        $this->assertStringContainsString(
            'x &lt; y &amp; y &gt; z &amp; "z &#x2265; w" &amp; \' w &#x2264; x \'',
            (string) $xmlFile,
        );
    }
}
