<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\State;

enum PlayMode: string
{
    case PREVIEW = 'preview'; // For previewing
    case FORMATIVE = 'formative'; // For practicing and learning
    case SUMMATIVE = 'summative'; // For assessing
}
