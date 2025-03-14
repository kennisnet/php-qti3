<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration;

use App\SharedKernel\Domain\AbstractCollection;

/**
 * @template-extends AbstractCollection<ResponseDeclaration>
 */
class ResponseDeclarationCollection extends AbstractCollection
{
    public function getType(): string
    {
        return ResponseDeclaration::class;
    }
}
