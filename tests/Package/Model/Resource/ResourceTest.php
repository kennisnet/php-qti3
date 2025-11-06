<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\Resource;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFile;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceType;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResourceTest extends TestCase
{
    #[Test]
    public function createResourceInstanceWithInvalidHrefThrowsException(): void
    {
        // Arrange & Act & Assert

        $this->expectException(InvalidArgumentException::class);

        new Resource(
            'test',
            ResourceType::ASSESSMENT_ITEM,
            'invalid.xml',
            new PackageFileCollection([
                new PackageFile('test.xml', new MemoryFileContent('content')),
            ]),
            new ManifestResourceDependencyCollection(),
        );
    }

    #[Test]
    public function getMainFileReturnsMainResourceFile(): void
    {
        // Arrange

        $resource = new Resource(
            'test',
            ResourceType::ASSESSMENT_ITEM,
            'test.xml',
            new PackageFileCollection([
                new PackageFile('test.xml', new MemoryFileContent('content')),
            ]),
            new ManifestResourceDependencyCollection(),
        );

        // Act

        $mainFile = $resource->getMainFile();

        // Assert

        $this->assertInstanceOf(PackageFile::class, $mainFile);
        $this->assertEquals('test.xml', $mainFile->getFilepath());
    }

    #[Test]
    public function getMainFileReturnsNullWhenHrefIsNull(): void
    {
        // Arrange

        $resource = new Resource(
            'test',
            ResourceType::ASSESSMENT_ITEM,
            null,
            new PackageFileCollection(),
            new ManifestResourceDependencyCollection(),
        );

        // Act

        $mainFile = $resource->getMainFile();

        // Assert

        $this->assertNull($mainFile);
    }
}
