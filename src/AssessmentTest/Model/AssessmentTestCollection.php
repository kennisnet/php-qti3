<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Model;

use Qti3\AbstractCollection;

/**
 * @template-extends AbstractCollection<AssessmentTest>
 */
class AssessmentTestCollection extends AbstractCollection
{
    public function getType(): string
    {
        return AssessmentTest::class;
    }
}
