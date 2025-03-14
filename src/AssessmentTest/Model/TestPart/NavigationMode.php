<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart;

enum NavigationMode: string
{
    case LINEAR = 'linear';
    case NONLINEAR = 'nonlinear';
}
