<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\Manifest;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Metadata\Metadata;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
use DateTimeImmutable;
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
            new DateTimeImmutable(),
        );
    }

    #[Test]
    public function addResourceFileUpdatesCollections(): void
    {
        $resourceFile = new Resource(
            'file',
            ResourceType::WEBCONTENT,
            'file',
            new ResourceFileCollection([
                new ResourceFile('file', new MemoryFileContent('test')),
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
            new ResourceFileCollection([
                new ResourceFile('file', new MemoryFileContent('test')),
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
                new Resource('id', ResourceType::ASSESSMENT_TEST, 'test.xml', new ResourceFileCollection([
                    new ResourceFile('test.xml', new MemoryFileContent('content')),
                ]), new ManifestResourceDependencyCollection()),
            ]),
        );

        $this->assertEquals('id', $qtiPackageMock->getAssessmentTestIdentifier());
    }
}
