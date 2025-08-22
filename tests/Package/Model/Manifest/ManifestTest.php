<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\Manifest;

use App\SharedKernel\Domain\Qti\Package\Model\Manifest\Manifest;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResource;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependency;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceType;
use App\SharedKernel\Domain\Qti\Shared\Xml\Reader\IXmlReader;
use App\SharedKernel\Infrastructure\Serializer\XmlParsingException;
use App\SharedKernel\Infrastructure\Serializer\XmlReader;
use DOMDocument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ManifestTest extends TestCase
{
    private Manifest $manifest;

    protected function setUp(): void
    {
        $this->manifest = ManifestMock::create();
    }

    #[Test]
    public function aValidFilenameCanBeGiven(): void
    {
        $this->assertEquals('imsmanifest.xml', $this->manifest->getFilepath());
    }

    #[Test]
    public function testGetIdentifier(): void
    {
        // Arrange
        $xmlContent = <<<XML
        <manifest identifier="manifest-001">
        </manifest>
        XML;

        $manifest = Manifest::fromString($xmlContent, new XmlReader());

        // Act
        $identifier = $manifest->getIdentifier();

        // Assert
        $this->assertSame('manifest-001', $identifier);
    }

    #[Test]
    public function testAddResourceAndGetResources(): void
    {
        // Arrange
        $xmlContent = <<<XML
        <manifest identifier="manifest-001">
        </manifest>
        XML;

        $manifest = Manifest::fromString($xmlContent, new XmlReader());

        $files = new ManifestResourceFileCollection();
        $files->add(new ManifestResourceFile('resource-001-dep'));

        $dependencies = new ManifestResourceDependencyCollection();
        $dependencies->add(new ManifestResourceDependency('resource-001-dep'));

        $newResource = new ManifestResource(
            'resource-001',
            ResourceType::WEBCONTENT,
            $files,
            $dependencies,
            'resource-001.html'
        );

        // Act
        $manifest->addResource($newResource);
        $resources = $manifest->getResources();

        // Assert
        $this->assertCount(1, $resources);
        $addedResource = $resources[0];

        $this->assertSame('resource-001', $addedResource->identifier);
        $this->assertSame('webcontent', $addedResource->type->value);
        $this->assertSame('resource-001.html', $addedResource->href);
        $this->assertCount(1, $addedResource->dependencies);
        $this->assertSame('resource-001-dep', $addedResource->dependencies[0]->identifierref);
    }

    #[Test]
    public function testGetResourcesFromXml(): void
    {
        // Arrange
        $xmlContent = <<<XML
        <manifest identifier="manifest-001">
            <resource identifier="resource-001" type="webcontent" href="resource-001.html">
                <dependency identifierref="resource-001-dep" />
            </resource>
        </manifest>
        XML;

        $manifest = Manifest::fromString($xmlContent, new XmlReader());

        // Act
        $resources = $manifest->getResources();

        // Assert
        $this->assertCount(1, $resources);

        $resource = $resources[0];
        $this->assertSame('resource-001', $resource->identifier);
        $this->assertSame('webcontent', $resource->type->value);
        $this->assertSame('resource-001.html', $resource->href);

        $this->assertCount(1, $resource->dependencies);
        $this->assertSame('resource-001-dep', $resource->dependencies[0]->identifierref);
    }

    #[Test]
    public function itThrowsExceptionWhenManifestIsInvalid(): void
    {
        // Arrange
        $invalidXmlContent = '<invalid></xml>';

        $xmlReader = $this->createMock(IXmlReader::class);
        $xmlReader->method('read')->with($invalidXmlContent)->willThrowException(new XmlParsingException());

        // Act & Assert
        $this->expectException(XmlParsingException::class);

        Manifest::fromString($invalidXmlContent, $xmlReader);
    }

    #[Test]
    public function itReadsTheManifestSuccessfully(): void
    {
        // Arrange
        $validXmlContent = '<manifest></manifest>';

        $domDocument = new DOMDocument();
        $domDocument->loadXML($validXmlContent);

        $xmlReader = $this->createMock(IXmlReader::class);
        $xmlReader->method('read')->with($validXmlContent)->willReturn($domDocument);

        // Act
        $manifest = Manifest::fromString($validXmlContent, $xmlReader);

        // Assert
        $this->assertInstanceOf(Manifest::class, $manifest);
    }

}
