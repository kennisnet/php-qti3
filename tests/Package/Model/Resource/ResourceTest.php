<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\Resource;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
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
            new ResourceFileCollection([
                new ResourceFile('test.xml', new MemoryFileContent('content')),
            ]),
            new ManifestResourceDependencyCollection()
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
            new ResourceFileCollection([
                new ResourceFile('test.xml', new MemoryFileContent('content')),
            ]),
            new ManifestResourceDependencyCollection()
        );

        // Act

        $mainFile = $resource->getMainFile();

        // Assert

        $this->assertInstanceOf(ResourceFile::class, $mainFile);
        $this->assertEquals('test.xml', $mainFile->href);
    }

    #[Test]
    public function getMainFileReturnsNullWhenHrefIsNull(): void
    {
        // Arrange

        $resource = new Resource(
            'test',
            ResourceType::ASSESSMENT_ITEM,
            null,
            new ResourceFileCollection(),
            new ManifestResourceDependencyCollection()
        );

        // Act

        $mainFile = $resource->getMainFile();

        // Assert

        $this->assertNull($mainFile);
    }
}
