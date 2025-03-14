<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration;

use App\SharedKernel\Domain\AbstractCollection;

/**
 * @template-extends AbstractCollection<OutcomeDeclaration>
 */
class OutcomeDeclarationCollection extends AbstractCollection
{
    public function getType(): string
    {
        return OutcomeDeclaration::class;
    }
}
