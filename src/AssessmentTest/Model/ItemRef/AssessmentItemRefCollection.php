<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Model\ItemRef;

use Qti3\AbstractCollection;

/** @template-extends AbstractCollection<AssessmentItemRef> */
class AssessmentItemRefCollection extends AbstractCollection
{
    public function getType(): string
    {
        return AssessmentItemRef::class;
    }
}
