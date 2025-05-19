<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\RubricBlock;

enum qtiUse: string
{
    case INSTRUCTIONS = 'instructions';
    case SCORING = 'scoring';
    case NAVIGATION = 'navigation';
}
