<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Downloader\Resource\IResourceDownloader;
use Qti3\Package\Filesystem\FlysystemMediaSource;
use Qti3\Package\Model\FileContent\FlysystemFileContent;
use Qti3\Package\Model\IMediaSource;
use Qti3\Package\Model\Manifest\Manifest;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\ResourceCollection;
use Qti3\Package\Model\Resource\WebcontentCollection;
use Qti3\Package\Service\WebcontentIdentifierGenerator;
use Qti3\Package\Service\WebcontentProcessor;
use Qti3\Package\Validator\Resource\IResourceValidator;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Xml\Reader\XmlReader;

class WebcontentProcessorPackageMediaTest extends TestCase
{
    private WebcontentProcessor $processor;
    private IMediaSource $mediaSource;

    protected function setUp(): void
    {
        parent::setUp();

        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $filesystem->write('logo.svg', '<svg/>');
        $this->mediaSource = new FlysystemMediaSource($filesystem);

        $this->processor = new WebcontentProcessor(
            $this->createStub(IResourceValidator::class),
            $this->createStub(IResourceDownloader::class),
            new WebcontentIdentifierGenerator(),
        );
    }

    #[Test]
    public function itIncludesAFileFromThePackageMediaSourceAsWebcontent(): void
    {
        // Arrange
        $webcontent = new WebcontentCollection();
        $warnings = new StringCollection();
        $element = $this->paragraphWithImage('logo.svg');

        // Act
        $dependencies = $this->processor->process($webcontent, $element, $warnings, null, $this->mediaSource);

        // Assert
        $this->assertCount(0, $warnings);
        $this->assertCount(1, $dependencies);
        $this->assertCount(1, $webcontent);
        $webcontentFile = $webcontent->first();
        $this->assertStringStartsWith('resources/', (string) $webcontentFile->href);
        $this->assertInstanceOf(FlysystemFileContent::class, $webcontentFile->getMainFile()?->getContent());
    }

    #[Test]
    public function itStillRefusesAPathTheMediaSourceDoesNotContain(): void
    {
        // Arrange
        $webcontent = new WebcontentCollection();
        $warnings = new StringCollection();
        $element = $this->paragraphWithImage('/etc/hostname');

        // Act
        $dependencies = $this->processor->process($webcontent, $element, $warnings, null, $this->mediaSource);

        // Assert
        $this->assertCount(0, $dependencies);
        $this->assertCount(0, $webcontent);
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Refused local file reference', $warnings->first());
    }

    #[Test]
    public function itRefusesATraversalAttemptWithAWarning(): void
    {
        // Arrange
        $webcontent = new WebcontentCollection();
        $warnings = new StringCollection();
        $element = $this->paragraphWithImage('../outside/secret.txt');

        // Act
        $dependencies = $this->processor->process($webcontent, $element, $warnings, null, $this->mediaSource);

        // Assert
        $this->assertCount(0, $dependencies);
        $this->assertCount(1, $warnings);
    }

    #[Test]
    public function itRefusesEveryLocalPathWithoutAMediaSource(): void
    {
        // Arrange
        $webcontent = new WebcontentCollection();
        $warnings = new StringCollection();
        $element = $this->paragraphWithImage('logo.svg');

        // Act
        $dependencies = $this->processor->process($webcontent, $element, $warnings, null);

        // Assert
        $this->assertCount(0, $dependencies);
        $this->assertCount(0, $webcontent);
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Refused local file reference', $warnings->first());
    }

    #[Test]
    public function findInvalidReferencesAcceptsAFileFromThePackageMediaSource(): void
    {
        // Without the media source the same reference is invalid (not in the
        // package); with it the reference resolves and the edit may proceed.
        $element = $this->paragraphWithImage('logo.svg');

        $this->assertCount(1, $this->processor->findInvalidReferences($element, $this->emptyPackage()));
        $this->assertCount(0, $this->processor->findInvalidReferences($element, $this->emptyPackage(), $this->mediaSource));
    }

    #[Test]
    public function findInvalidReferencesStillRejectsAPathTheMediaSourceDoesNotContain(): void
    {
        $element = $this->paragraphWithImage('missing.svg');

        $invalid = $this->processor->findInvalidReferences($element, $this->emptyPackage(), $this->mediaSource);

        $this->assertCount(1, $invalid);
        $this->assertStringContainsString('missing.svg', $invalid[0]);
    }

    #[Test]
    public function findInvalidReferencesStillRejectsATraversalAttempt(): void
    {
        $element = $this->paragraphWithImage('../outside/secret.txt');

        $invalid = $this->processor->findInvalidReferences($element, $this->emptyPackage(), $this->mediaSource);

        $this->assertCount(1, $invalid);
        $this->assertStringContainsString('outside the package', $invalid[0]);
    }

    private function emptyPackage(): QtiPackage
    {
        $manifest = Manifest::fromString(
            '<manifest xmlns="http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1" identifier="M"><organizations/><resources/></manifest>',
            new XmlReader(),
        );

        return new QtiPackage(new ResourceCollection(), $manifest);
    }

    private function paragraphWithImage(string $source): HTMLTag
    {
        return new HTMLTag('p', [], [
            new HTMLTag('img', ['src' => $source, 'alt' => 'media']),
        ]);
    }
}
