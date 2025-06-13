<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;

class Equal extends AbstractQtiExpression
{
    public function __construct(
        public readonly AbstractQtiExpression $expression1,
        public readonly AbstractQtiExpression $expression2,
    ) {}

    public function children(): array
    {
        return [
            $this->expression1,
            $this->expression2,
        ];
    }

    public function evaluate(ItemState $state): bool
    {
        $value1 = $this->expression1->evaluateNumber($state);
        $value2 = $this->expression2->evaluateNumber($state);

        return (float) $value1 === (float) $value2;
    }

    public function getBaseType(ItemState $state): BaseType
    {
        return BaseType::BOOLEAN;
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::SINGLE;
    }

    public function validate(StringCollection $identifiers): StringCollection
    {
        return $this->expression1->validate($identifiers)->mergeWith($this->expression2->validate($identifiers));
    }
}
