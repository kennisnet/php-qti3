<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;

class Contains extends AbstractQtiExpression
{
    public function __construct(
        private readonly AbstractQtiExpression $container,
        private readonly AbstractQtiExpression $contains
    ) {}

    public function children(): array
    {
        return [$this->container, $this->contains];
    }

    public function evaluate(ItemState $state): bool
    {
        $container = $this->container->evaluateArray($state);
        $contains = $this->contains->evaluateArray($state);

        return array_all($contains, fn($item): bool => in_array($item, $container));
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
        return $this->container->validate($itemState)->mergeWith($this->contains->validate($itemState));
    }
}
