<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\ResourceFile;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;

class AssessmentItemMock extends Resource
{
    public function __construct(string $identifier)
    {
        parent::__construct(
            $identifier,
            ResourceType::ASSESSMENT_ITEM,
            $identifier . '.xml',
            new ResourceFileCollection([
                new ResourceFile($identifier . '.xml', new MemoryFileContent('content')),
            ]),
            new ManifestResourceDependencyCollection(),
        );
    }
}
