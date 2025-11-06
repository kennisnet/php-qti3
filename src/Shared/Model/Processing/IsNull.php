<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;

class IsNull extends AbstractQtiExpression
{
    public function __construct(
        public readonly Variable $variable,
    ) {}

    public function children(): array
    {
        return [
            $this->variable,
        ];
    }

    public function evaluate(ItemState $state): bool
    {
        return $this->variable->evaluate($state) === null;
    }

    public function getBaseType(ItemState $state): BaseType
    {
        return BaseType::BOOLEAN;
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::SINGLE;
    }

    public function validate(ItemState $itemState): StringCollection
    {
        return $this->variable->validate($itemState);
    }
}
