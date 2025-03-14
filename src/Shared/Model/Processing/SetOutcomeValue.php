<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class SetOutcomeValue extends QtiElement implements IProcessingElement
{
    public function __construct(
        public readonly string $identifier,
        public readonly IQtiExpression $value
    ) {}

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
        ];
    }

    public function children(): array
    {
        return [
            $this->value,
        ];
    }
}
