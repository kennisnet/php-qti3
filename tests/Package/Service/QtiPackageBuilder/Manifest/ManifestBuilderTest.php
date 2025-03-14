<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\Manifest;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\Manifest;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\Manifest\ManifestBuilder;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\Manifest\MetadataBuilder;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\Manifest\OrganizationsBuilder;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\Manifest\ResourcesBuilder;
use App\SharedKernel\Infrastructure\Serializer\XmlBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ManifestBuilderTest extends TestCase
{
    private XmlBuilder $xmlBuilder;
    private MetadataBuilder|MockObject $metadataBuilder;
    private OrganizationsBuilder|MockObject $organizationsBuilder;
    private ResourcesBuilder|MockObject $resourcesBuilder;
    private ManifestBuilder $manifestBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xmlBuilder = new XmlBuilder();
        $this->metadataBuilder = $this->createMock(MetadataBuilder::class);
        $this->organizationsBuilder = $this->createMock(OrganizationsBuilder::class);
        $this->resourcesBuilder = $this->createMock(ResourcesBuilder::class);

        $this->manifestBuilder = new ManifestBuilder(
            $this->xmlBuilder,
            $this->metadataBuilder,
            $this->organizationsBuilder,
            $this->resourcesBuilder,
        );
    }

    #[Test]
    public function aManifestCanBeCreatedForResources(): void
    {
        $manifest = $this->manifestBuilder->buildForResources(new ResourceCollection([
            new Resource('id', ResourceType::WEBCONTENT, 'file.txt', new ResourceFileCollection([
                new ResourceFile('file.txt', new MemoryFileContent('content')),
            ]), new ManifestResourceDependencyCollection()),
        ]));
        $this->assertInstanceOf(Manifest::class, $manifest);
    }
}
