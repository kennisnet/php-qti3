<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\AbstractQtiExpression;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class OutcomeElseIf extends QtiElement
{
    public function __construct(
        public readonly AbstractQtiExpression $condition,
        public readonly SetOutcomeValue $setOutcomeValue,
    ) {}

    public function children(): array
    {
        return [
            $this->condition,
            $this->setOutcomeValue,
        ];
    }
}
