<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\OrderInteraction;

enum Orientation: string
{
    case HORIZONTAL = 'horizontal';
    case VERTICAL = 'vertical';
}
