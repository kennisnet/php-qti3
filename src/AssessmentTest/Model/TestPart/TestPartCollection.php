<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart;

use App\SharedKernel\Domain\AbstractCollection;

/**
 * @template-extends AbstractCollection<TestPart>
 */
class TestPartCollection extends AbstractCollection
{
    public function getType(): string
    {
        return TestPart::class;
    }
}
