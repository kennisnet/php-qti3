<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model;

use App\SharedKernel\Domain\AbstractCollection;

/**
 * @template-extends AbstractCollection<QtiPackage>
 */
class QtiPackageCollection extends AbstractCollection
{
    protected function getType(): string
    {
        return QtiPackage::class;
    }
}
