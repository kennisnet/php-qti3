<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Manifest;

use App\SharedKernel\Domain\AbstractCollection;

/**
 * @template-extends AbstractCollection<ManifestResourceDependency>
 */
class ManifestResourceDependencyCollection extends AbstractCollection
{
    protected function getType(): string
    {
        return ManifestResourceDependency::class;
    }
}
