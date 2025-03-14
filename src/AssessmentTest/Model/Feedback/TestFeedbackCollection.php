<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model\Feedback;

use App\SharedKernel\Domain\AbstractCollection;

/**
 * @template-extends AbstractCollection<TestFeedback>
 */
class TestFeedbackCollection extends AbstractCollection
{
    public function getType(): string
    {
        return TestFeedback::class;
    }
}
