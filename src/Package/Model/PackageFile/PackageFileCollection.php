<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\PackageFile;

use App\SharedKernel\Domain\AbstractCollection;

/**
 * @template-extends AbstractCollection<PackageFile>
 */
class PackageFileCollection extends AbstractCollection
{
    protected function getType(): string
    {
        return PackageFile::class;
    }
}
