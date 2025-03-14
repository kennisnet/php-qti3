<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\ResourceFile;

use App\SharedKernel\Domain\AbstractCollection;

/**
 * @template-extends AbstractCollection<ResourceFile>
 */
class ResourceFileCollection extends AbstractCollection
{
    protected function getType(): string
    {
        return ResourceFile::class;
    }
}
