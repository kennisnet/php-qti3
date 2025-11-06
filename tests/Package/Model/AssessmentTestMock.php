<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Metadata\Metadata;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFile;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceType;

class AssessmentTestMock extends Resource
{
    public function __construct(
        string $identifier,
        ?Metadata $metadata = null,
    ) {
        parent::__construct(
            $identifier,
            ResourceType::ASSESSMENT_TEST,
            $identifier . '.xml',
            new PackageFileCollection([
                new PackageFile($identifier . '.xml', new MemoryFileContent('content')),
            ]),
            new ManifestResourceDependencyCollection(),
            $metadata,
        );
    }
}
