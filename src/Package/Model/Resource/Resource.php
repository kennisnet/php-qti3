<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Resource;

use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Metadata\Metadata;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;

class Resource
{
    public function __construct(
        public readonly string $identifier,
        public readonly ResourceType $type,
        public readonly ?string $href,
        public readonly ResourceFileCollection $files,
        public readonly ManifestResourceDependencyCollection $resourceDependencies,
        public ?Metadata $metadata = null,
    ) {}
}
