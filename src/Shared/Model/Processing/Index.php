<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;

class Index extends AbstractQtiExpression
{
    public function __construct(
        private readonly AbstractQtiExpression $container,
        private readonly IndexExpression $n
    ) {}

    public function children(): array
    {
        return [$this->container];
    }

    public function attributes(): array
    {
        return [
            'n' => $this->n->value,
        ];
    }

    public function evaluate(ItemState $state): mixed
    {
        $container = $this->container->evaluateArray($state);
        $index = $this->n->evaluate($state);

        // QTI indices are 1-based, but PHP arrays are 0-based
        $phpIndex = $index - 1;

        if (!array_key_exists($phpIndex, $container)) {
            return null;
        }

        return $container[$phpIndex];
    }

    public function getBaseType(ItemState $state): BaseType
    {
        return $this->container->getBaseType($state);
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::SINGLE;
    }

    public function validate(ItemState $itemState): StringCollection
    {
        return $this->container->validate($itemState);
    }
}
