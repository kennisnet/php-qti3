<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\AbstractQtiExpression;
use App\SharedKernel\Domain\Qti\State\ItemState;

class TestVariables extends AbstractQtiExpression
{
    public function __construct(
        public readonly string $variableIdentifier,
        public readonly ?string $includeCategory = null,
    ) {}

    public function attributes(): array
    {
        return [
            'variable-identifier' => $this->variableIdentifier,
            'include-category' => $this->includeCategory,
        ];
    }

    // @codeCoverageIgnoreStart
    public function evaluate(ItemState $state): mixed
    {
        return null;
    }
    // @codeCoverageIgnoreEnd
}
