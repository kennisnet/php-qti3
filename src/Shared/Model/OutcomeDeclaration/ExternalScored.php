<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration;

enum ExternalScored: string
{
    case HUMAN = 'human';
    case EXTERNAL_MACHINE = 'externalMachine';
}
