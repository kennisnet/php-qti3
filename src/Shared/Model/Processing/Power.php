<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;

class Power extends AbstractQtiExpression
{
    public function __construct(
        private readonly AbstractQtiExpression $base,
        private readonly AbstractQtiExpression $exponent
    ) {}

    public function children(): array
    {
        return [$this->base, $this->exponent];
    }

    public function evaluate(ItemState $state): float|int
    {
        $base = $this->base->evaluateNumber($state);
        $exponent = $this->exponent->evaluateNumber($state);

        return $base ** $exponent;
    }

    public function getBaseType(ItemState $state): BaseType
    {
        return $this->base->getBaseType($state);
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::SINGLE;
    }

    public function validate(ItemState $itemState): StringCollection
    {
        return $this->base->validate($itemState)->mergeWith($this->exponent->validate($itemState));
    }
}
