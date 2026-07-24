<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Downloader\Resource\IResourceDownloader;
use Qti3\Package\Model\Manifest\Manifest;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\ResourceCollection;
use Qti3\Package\Service\WebcontentIdentifierGenerator;
use Qti3\Package\Service\WebcontentProcessor;
use Qti3\Package\Validator\Resource\IResourceValidator;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Xml\Reader\XmlReader;

final class WebcontentProcessorTest extends TestCase
{
    private function processor(): WebcontentProcessor
    {
        return new WebcontentProcessor(
            $this->createStub(IResourceValidator::class),
            $this->createStub(IResourceDownloader::class),
            new WebcontentIdentifierGenerator(),
        );
    }

    private function emptyPackage(): QtiPackage
    {
        $manifest = Manifest::fromString(
            '<manifest xmlns="http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1" identifier="M"><organizations/><resources/></manifest>',
            new XmlReader(),
        );

        return new QtiPackage(new ResourceCollection(), $manifest);
    }

    #[Test]
    public function findInvalidReferencesValidatesTheRootElementItself(): void
    {
        // The root element is itself a resource provider (an <img>); its own
        // reference must be checked, not only its descendants'.
        $img = new HTMLTag('img', ['src' => 'resources/missing.png', 'alt' => '']);

        $invalid = $this->processor()->findInvalidReferences($img, $this->emptyPackage());

        $this->assertCount(1, $invalid);
        $this->assertStringContainsString('resources/missing.png', $invalid[0]);
    }

    #[Test]
    public function findInvalidReferencesReportsRootAndDescendantExactlyOnceEach(): void
    {
        // A provider root that is invalid *and* contains an invalid provider
        // child: each is reported once — no double counting.
        $root = new HTMLTag('img', ['src' => 'resources/root.png', 'alt' => ''], [
            new HTMLTag('img', ['src' => 'resources/child.png', 'alt' => '']),
        ]);

        $invalid = $this->processor()->findInvalidReferences($root, $this->emptyPackage());

        $this->assertCount(2, $invalid);
        $this->assertStringContainsString('resources/root.png', implode("\n", $invalid));
        $this->assertStringContainsString('resources/child.png', implode("\n", $invalid));
    }

    #[Test]
    public function findInvalidReferencesAcceptsValidReferences(): void
    {
        // data: URI, http(s) URL and a trusted asset are all valid.
        $root = new HTMLTag('p', [], [
            new HTMLTag('img', ['src' => 'data:image/png;base64,AAAA', 'alt' => '']),
            new HTMLTag('img', ['src' => 'https://example.com/x.png', 'alt' => '']),
        ]);

        $this->assertSame([], $this->processor()->findInvalidReferences($root, $this->emptyPackage()));
    }
}
