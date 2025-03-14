<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IBooleanExpression;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class ResponseElseIf extends QtiElement
{
    public function __construct(
        public readonly IBooleanExpression $condition,
        public readonly SetOutcomeValue $setOutcomeValue
    ) {}

    public function children(): array
    {
        return [
            $this->condition,
            $this->setOutcomeValue,
        ];
    }
}
