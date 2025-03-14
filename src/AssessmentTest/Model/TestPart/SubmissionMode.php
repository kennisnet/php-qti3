<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart;

enum SubmissionMode: string
{
    case INDIVIDUAL = 'individual';
    case SIMULTANEOUS = 'simultaneous';
}
