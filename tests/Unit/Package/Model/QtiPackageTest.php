<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Model;

use Qti3\Package\Exception\InvalidQtiPackageException;
use Qti3\Package\Model\FileContent\MemoryFileContent;
use Qti3\Package\Model\Manifest\Manifest;
use Qti3\Package\Model\Manifest\ManifestResourceDependencyCollection;
use Qti3\Package\Model\Metadata\Metadata;
use Qti3\Package\Model\PackageFile\AssessmentTestFile;
use Qti3\Package\Model\PackageFile\PackageFile;
use Qti3\Package\Model\PackageFile\PackageFileCollection;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Model\Resource\ResourceCollection;
use Qti3\Package\Model\Resource\ResourceType;
use Qti3\Shared\Exception\ResourceNotFoundException;
use Qti3\Shared\Xml\Reader\XmlReader;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QtiPackageTest extends TestCase
{
    private QtiPackage $qtiPackage;
    private ResourceCollection $resources;
    private Manifest $manifest;

    protected function setUp(): void
    {
        $this->resources = $this->createMock(ResourceCollection::class);
        $this->manifest = $this->createMock(Manifest::class);

        $this->qtiPackage = new QtiPackage(
            $this->resources,
            $this->manifest,
        );
    }

    #[Test]
    public function addResourceFileUpdatesCollections(): void
    {
        $resourceFile = new Resource(
            'file',
            ResourceType::WEBCONTENT,
            'file',
            new PackageFileCollection([
                new PackageFile('file', new MemoryFileContent('test')),
            ]),
            new ManifestResourceDependencyCollection(),
        );

        $this->resources
            ->expects($this->once())
            ->method('add')
            ->with($resourceFile);

        $this->manifest
            ->expects($this->once())
            ->method('addResource');

        $this->qtiPackage->addResource($resourceFile);
    }

    #[Test]
    public function getFilesReturnsCorrectCollection(): void
    {
        $resourceFile = new Resource(
            'file',
            ResourceType::WEBCONTENT,
            'file',
            new PackageFileCollection([
                new PackageFile('file', new MemoryFileContent('test')),
            ]),
            new ManifestResourceDependencyCollection(),
        );

        $this->resources
            ->method('all')
            ->willReturn([$resourceFile]);

        $packageFiles = $this->qtiPackage->getFiles();

        $this->assertInstanceOf(PackageFileCollection::class, $packageFiles);
    }

    #[Test]
    public function getMetadataReturnsMetadataIfAssessmentTestExists(): void
    {
        $metadata = $this->createMock(Metadata::class);
        $assessmentTestFile = $this->createMock(Resource::class);
        $assessmentTestFile->metadata = $metadata;

        $filteredCollection = $this->createMock(ResourceCollection::class);
        $filteredCollection
            ->method('first')
            ->willReturn($assessmentTestFile);

        $this->resources
            ->method('filterByType')
            ->with(ResourceType::ASSESSMENT_TEST)
            ->willReturn($filteredCollection);

        $this->assertSame($metadata, $this->qtiPackage->getMetadata());
    }

    #[Test]
    public function getMetadataReturnsNullIfNoAssessmentTestExists(): void
    {
        $filteredCollection = $this->createMock(ResourceCollection::class);
        $filteredCollection
            ->method('first')
            ->willReturn(null);

        $this->resources
            ->method('filterByType')
            ->with(ResourceType::ASSESSMENT_TEST)
            ->willReturn($filteredCollection);

        $this->assertNull($this->qtiPackage->getMetadata());
    }

    #[Test]
    public function getAssessmentTestIdentifierExists(): void
    {
        $qtiPackageMock = new QtiPackageMock(
            resources: new ResourceCollection([
                new Resource('id', ResourceType::ASSESSMENT_TEST, 'test.xml', new PackageFileCollection([
                    new PackageFile('test.xml', new MemoryFileContent('content')),
                ]), new ManifestResourceDependencyCollection()),
            ]),
        );

        $this->assertEquals('id', $qtiPackageMock->getAssessmentTestIdentifier());
    }

    #[Test]
    public function hasFileReturnsIfPackageHasFile(): void
    {
        $qtiPackageMock = new QtiPackageMock();

        $this->assertTrue($qtiPackageMock->hasFile('test.xml'));
        $this->assertFalse($qtiPackageMock->hasFile('non-existing.xml'));
    }

    #[Test]
    public function addItemRegistersManifestResourceAndAppendsItemRef(): void
    {
        $package = $this->editablePackage();
        $item = Resource::assessmentItem('ITEM001', $this->itemXml('PLACEHOLDER'), new XmlReader());

        $package->addItem($item);

        $this->assertSame(['ITEM001'], $package->getItemIdentifiers());
        $this->assertStringContainsString('identifier="ITEM001"', (string) $item->getMainFile());
        $this->assertStringContainsString('ITEM001.xml', (string) $package->manifest);
        $this->assertStringContainsString('qti-assessment-item-ref', (string) $package->getAssessmentTestFile());
    }

    #[Test]
    public function addItemRejectsAResourceThatIsNotAnAssessmentItem(): void
    {
        $package = $this->editablePackage();

        $this->expectException(InvalidArgumentException::class);

        $package->addItem(new Resource(
            'file',
            ResourceType::WEBCONTENT,
            'file',
            new PackageFileCollection([new PackageFile('file', new MemoryFileContent('test'))]),
            new ManifestResourceDependencyCollection(),
        ));
    }

    #[Test]
    public function updateItemReplacesTheItemContentAndKeepsTheIdentifier(): void
    {
        $package = $this->editablePackage();
        $package->addItem(Resource::assessmentItem('ITEM001', $this->itemXml('Vraag'), new XmlReader()));

        $updated = $package->updateItem('ITEM001', $this->itemXml('Bijgewerkte vraag'));

        $this->assertStringContainsString('Bijgewerkte vraag', (string) $updated->getMainFile());
        $this->assertStringContainsString('identifier="ITEM001"', (string) $updated->getMainFile());
    }

    #[Test]
    public function updateItemThrowsForAnUnknownItem(): void
    {
        $package = $this->editablePackage();

        $this->expectException(ResourceNotFoundException::class);

        $package->updateItem('ITEM999', $this->itemXml('Vraag'));
    }

    #[Test]
    public function getAssessmentTestFileThrowsWhenThePackageHasNoTestResource(): void
    {
        $package = new QtiPackage(
            new ResourceCollection(),
            Manifest::fromString('<manifest identifier="M"/>', new XmlReader()),
        );

        $this->expectException(InvalidQtiPackageException::class);

        $package->getAssessmentTestFile();
    }

    private function editablePackage(): QtiPackage
    {
        $xmlReader = new XmlReader();
        $asiNamespace = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';

        $testXml = sprintf(
            '<qti-assessment-test xmlns="%s" identifier="test-1" title="">'
            . '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">'
            . '<qti-assessment-section identifier="s" title="" visible="true"/>'
            . '</qti-test-part></qti-assessment-test>',
            $asiNamespace,
        );

        return new QtiPackage(
            new ResourceCollection([
                new Resource(
                    'test',
                    ResourceType::ASSESSMENT_TEST,
                    'AssessmentTest.xml',
                    new PackageFileCollection([
                        new AssessmentTestFile('AssessmentTest.xml', new MemoryFileContent($testXml), $xmlReader),
                    ]),
                    new ManifestResourceDependencyCollection(),
                ),
            ]),
            Manifest::fromString(
                '<manifest xmlns="http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1" identifier="M"><resources/></manifest>',
                $xmlReader,
            ),
        );
    }

    private function itemXml(string $title): string
    {
        return sprintf(
            '<qti-assessment-item xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0"'
            . ' identifier="X" title="%s" time-dependent="false"><qti-item-body/></qti-assessment-item>',
            $title,
        );
    }
}
