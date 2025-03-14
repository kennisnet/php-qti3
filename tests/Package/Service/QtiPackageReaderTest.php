<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Service;

use App\SharedKernel\Domain\Qti\Package\Model\IPackageReader;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\IManifestFactory;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\Manifest;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResource;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependency;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackageId;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
use App\SharedKernel\Domain\Qti\Package\Service\IPackageFactory;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageReader;
use App\SharedKernel\Domain\Qti\Shared\Xml\Reader\IXmlReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QtiPackageReaderTest extends TestCase
{
    private IManifestFactory $manifestFactory;
    private IXmlReader $xmlReader;
    private IPackageFactory $zipPackageFactory;
    private IPackageFactory $filesystemPackageFactory;
    private IPackageReader $packageReader;
    private Manifest $manifest;
    private QtiPackageReader $qtiPackageReader;

    protected function setUp(): void
    {
        $this->manifestFactory = $this->createMock(IManifestFactory::class);
        $this->xmlReader = $this->createMock(IXmlReader::class);
        $this->zipPackageFactory = $this->createMock(IPackageFactory::class);
        $this->filesystemPackageFactory = $this->createMock(IPackageFactory::class);
        $this->packageReader = $this->createMock(IPackageReader::class);
        $this->manifest = $this->createMock(Manifest::class);

        $this->qtiPackageReader = new QtiPackageReader(
            $this->manifestFactory,
            $this->xmlReader,
            $this->zipPackageFactory,
            $this->filesystemPackageFactory
        );
    }

    public function testFromFilesystem(): void
    {
        $filePath = '/path/to/qti-package.xml';
        $qtiPackageId = QtiPackageId::generate();

        $this->filesystemPackageFactory
            ->method('getReader')
            ->with($filePath)
            ->willReturn($this->packageReader);

        $this->manifestFactory
            ->method('createFromXmlString')
            ->willReturn($this->manifest);

        $manifestResource = new ManifestResource('identifier', ResourceType::ASSESSMENT_TEST, new ManifestResourceFileCollection([
            new ManifestResourceFile('file.xml'),
        ]), new ManifestResourceDependencyCollection(), 'file.xml');

        $this->manifest
            ->method('getResources')
            ->willReturn(new ManifestResourceCollection([$manifestResource]));

        $this->packageReader
            ->method('readFile')
            ->willReturn('<xml></xml>');

        $qtiPackage = $this->qtiPackageReader->fromFilesystem($filePath, $qtiPackageId);

        $this->assertInstanceOf(QtiPackage::class, $qtiPackage);
        $this->assertEquals($qtiPackageId, $qtiPackage->id);
    }

    #[Test]
    public function readPackageFromZip(): void
    {
        $filePath = '/path/to/qti-package.zip';
        $qtiPackageId = QtiPackageId::generate();

        $this->zipPackageFactory
            ->method('getReader')
            ->with($filePath)
            ->willReturn($this->packageReader);

        $manifestResource = new ManifestResource('identifier', ResourceType::ASSESSMENT_TEST, new ManifestResourceFileCollection(), new ManifestResourceDependencyCollection(), 'href');

        $this->manifestFactory
            ->method('createFromXmlString')
            ->willReturn($this->manifest);

        $this->manifest
            ->method('getResources')
            ->willReturn(new ManifestResourceCollection([$manifestResource]));

        $this->packageReader
            ->method('readFile')
            ->willReturn('<xml></xml>');

        $qtiPackage = $this->qtiPackageReader->fromZip($filePath, $qtiPackageId);

        $this->assertInstanceOf(QtiPackage::class, $qtiPackage);
        $this->assertEquals($qtiPackageId, $qtiPackage->id);
    }

    #[Test]
    public function readPackageWithMetadataFile(): void
    {
        $resource1 = new ManifestResource(
            'resource1',
            ResourceType::ASSESSMENT_TEST,
            new ManifestResourceFileCollection(),
            new ManifestResourceDependencyCollection([new ManifestResourceDependency('resource4')]),
            'file1.xml'
        );

        $resource2 = new ManifestResource(
            'resource2',
            ResourceType::ASSESSMENT_ITEM,
            new ManifestResourceFileCollection([
                new ManifestResourceFile('image.jpeg'),
            ]),
            new ManifestResourceDependencyCollection(),
            'file2.xml'
        );

        $resource3 = new ManifestResource(
            'resource3',
            ResourceType::WEBCONTENT,
            new ManifestResourceFileCollection(),
            new ManifestResourceDependencyCollection(),
            'file3.css'
        );

        $resource4 = new ManifestResource(
            'resource4',
            ResourceType::RESOURCE_METADATA,
            new ManifestResourceFileCollection(),
            new ManifestResourceDependencyCollection(),
            'file4.xml'
        );

        $filePath = '/path/to/qti-package.zip';
        $qtiPackageId = QtiPackageId::generate();

        $this->zipPackageFactory
            ->method('getReader')
            ->with($filePath)
            ->willReturn($this->packageReader);

        $this->manifestFactory
            ->method('createFromXmlString')
            ->willReturn($this->manifest);

        $this->manifest
            ->method('getResources')
            ->willReturn(new ManifestResourceCollection([$resource1, $resource2, $resource3, $resource4]));

        $this->packageReader
            ->method('readFile')
            ->willReturn('<xml></xml>');

        $qtiPackage = $this->qtiPackageReader->fromZip($filePath, $qtiPackageId);

        $this->assertInstanceOf(QtiPackage::class, $qtiPackage);
        $this->assertEquals($qtiPackageId, $qtiPackage->id);
    }

    #[Test]
    public function readPackageWithoutMetadataFile(): void
    {
        $resource1 = new ManifestResource(
            'resource1',
            ResourceType::ASSESSMENT_TEST,
            new ManifestResourceFileCollection(),
            new ManifestResourceDependencyCollection([new ManifestResourceDependency('resource4')]),
            'file1.xml'
        );

        $resource2 = new ManifestResource(
            'resource2',
            ResourceType::ASSESSMENT_ITEM,
            new ManifestResourceFileCollection([
                new ManifestResourceFile('image.jpeg'),
            ]),
            new ManifestResourceDependencyCollection(),
            'file2.xml'
        );

        $resource3 = new ManifestResource(
            'resource3',
            ResourceType::WEBCONTENT,
            new ManifestResourceFileCollection(),
            new ManifestResourceDependencyCollection(),
            'file3.css'
        );

        $filePath = '/path/to/qti-package.zip';
        $qtiPackageId = QtiPackageId::generate();

        $this->zipPackageFactory
            ->method('getReader')
            ->with($filePath)
            ->willReturn($this->packageReader);

        $this->manifestFactory
            ->method('createFromXmlString')
            ->willReturn($this->manifest);

        $this->manifest
            ->method('getResources')
            ->willReturn(new ManifestResourceCollection([$resource1, $resource2, $resource3]));

        $this->packageReader
            ->method('readFile')
            ->willReturn('<xml></xml>');

        $qtiPackage = $this->qtiPackageReader->fromZip($filePath, $qtiPackageId);

        $this->assertInstanceOf(QtiPackage::class, $qtiPackage);
        $this->assertEquals($qtiPackageId, $qtiPackage->id);
    }
}
