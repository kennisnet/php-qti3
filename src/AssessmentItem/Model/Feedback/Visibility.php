<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Feedback;

enum Visibility: string
{
    case SHOW = 'show';
    case HIDE = 'hide';
}
