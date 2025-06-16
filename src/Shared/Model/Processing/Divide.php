<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;

class Divide extends AbstractQtiExpression
{
    public function __construct(
        public readonly AbstractQtiExpression $element1,
        public readonly AbstractQtiExpression $element2
    ) {}

    public function children(): array
    {
        return [
            $this->element1,
            $this->element2,
        ];
    }

    public function evaluate(ItemState $state): int|float
    {
        $value1 = $this->element1->evaluateNumber($state);
        $value2 = $this->element2->evaluateNumber($state);

        return $value1 / $value2;
    }

    public function getBaseType(ItemState $state): BaseType
    {
        return BaseType::FLOAT;
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::SINGLE;
    }

    public function validate(ItemState $itemState): StringCollection
    {
        return $this->element1->validate($itemState)->mergeWith($this->element2->validate($itemState));
    }
}
