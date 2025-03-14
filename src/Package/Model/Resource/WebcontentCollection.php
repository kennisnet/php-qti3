<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Resource;

use App\SharedKernel\Domain\AbstractCollection;

/**
 * @template-extends AbstractCollection<Webcontent>
 */
class WebcontentCollection extends AbstractCollection
{
    public function findByOriginalPath(string $originalPath): ?Webcontent
    {
        return $this->filter(
            fn(Webcontent $webcontent): bool => $webcontent->originalPath === $originalPath
        )->first();
    }

    protected function getType(): string
    {
        return Webcontent::class;
    }
}
