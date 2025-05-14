<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class OutcomeCondition extends QtiElement implements IOutcomeProcessingElement
{
    public function __construct(
        public readonly OutcomeIf $if,
        /** @var array<int, OutcomeElseIf> */
        public readonly array $elseIfs = [],
        public readonly ?OutcomeElse $else = null,
    ) {}

    public function children(): array
    {
        return [
            $this->if,
            ...$this->elseIfs,
            $this->else,
        ];
    }
}
