<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\Manifest;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResource;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ManifestResourceTest extends TestCase
{
    #[Test]
    public function testFromResourceFile(): void
    {
        $resourceFile = new Resource(
            'id',
            ResourceType::WEBCONTENT,
            'file.txt',
            new ResourceFileCollection([
                new ResourceFile('file.txt', new MemoryFileContent('content')),
            ]),
            new ManifestResourceDependencyCollection(),
        );

        $manifestResource = ManifestResource::fromResourceFile($resourceFile);

        $this->assertEquals($resourceFile->identifier, $manifestResource->identifier);
    }

    #[Test]
    public function testMetadataResourceThrowsExceptionWhenHrefIsNull(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ManifestResource(
            'id',
            ResourceType::RESOURCE_METADATA,
            new ManifestResourceFileCollection(),
            new ManifestResourceDependencyCollection(),
        );
    }
}
