<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\INumericExpression;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class TestVariables extends QtiElement implements INumericExpression
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
}
