<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef;

use App\SharedKernel\Domain\AbstractCollection;

/** @template-extends AbstractCollection<AssessmentItemRef> */
class AssessmentItemRefCollection extends AbstractCollection
{
    public function getType(): string
    {
        return AssessmentItemRef::class;
    }
}
