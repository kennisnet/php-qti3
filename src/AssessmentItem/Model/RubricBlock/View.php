<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\RubricBlock;

enum View: string
{
    case AUTHOR = 'author';
    case CANDIDATE = 'candidate';
    case PROCTOR = 'proctor';
    case SCORER = 'scorer';
    case TEST_CONSTRUCTOR = 'testConstructor';
    case TUTOR = 'tutor';
}
