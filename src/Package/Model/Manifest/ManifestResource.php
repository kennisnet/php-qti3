<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Manifest;

use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
use InvalidArgumentException;

readonly class ManifestResource
{
    public function __construct(
        public string $identifier,
        public ResourceType $type,
        public ManifestResourceFileCollection $files,
        public ManifestResourceDependencyCollection $dependencies,
        public ?string $href = null
    ) {
        if ($type->requiresHref() && empty($href)) {
            throw new InvalidArgumentException(sprintf('Resource type %s requires href', $type->value));
        }
    }

    public static function fromResourceFile(Resource $resource): self
    {
        return new self(
            $resource->identifier,
            $resource->type,
            new ManifestResourceFileCollection(array_map(
                fn($file): ManifestResourceFile => new ManifestResourceFile($file->href, ),
                $resource->files->all()
            )),
            $resource->resourceDependencies,
            $resource->href
        );
    }
}
