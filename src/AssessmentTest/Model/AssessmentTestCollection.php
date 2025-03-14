<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model;

use App\SharedKernel\Domain\AbstractCollection;

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
