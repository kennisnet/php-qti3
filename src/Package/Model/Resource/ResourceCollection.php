<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Resource;

use App\SharedKernel\Domain\AbstractCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;

/**
 * @template-extends AbstractCollection<Resource>
 */
class ResourceCollection extends AbstractCollection
{
    public function filterByType(ResourceType $type): ResourceCollection
    {
        return $this->filter(fn(Resource $resource): bool => $resource->type === $type);
    }

    protected function getType(): string
    {
        return Resource::class;
    }
}
