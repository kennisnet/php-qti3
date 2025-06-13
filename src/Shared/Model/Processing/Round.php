<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;

class Round extends AbstractQtiExpression
{
    public function __construct(
        private readonly AbstractQtiExpression $expression,
        private readonly string $roundingMode = 'nearest'
    ) {}

    public function children(): array
    {
        return [$this->expression];
    }

    public function evaluate(ItemState $state): int|float
    {
        $value = $this->expression->evaluateNumber($state);

        return match ($this->roundingMode) {
            'floor' => floor($value),
            'ceiling' => ceil($value),
            default => round($value),
        };
    }

    public function getBaseType(ItemState $state): BaseType
    {
        return BaseType::INTEGER;
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::SINGLE;
    }

    public function validate(StringCollection $identifiers): StringCollection
    {
        return $this->expression->validate($identifiers);
    }
}
