<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model\Feedback;

enum TestFeedbackAccess: string
{
    case AT_END = 'atEnd';
    case DURING = 'during';
}
