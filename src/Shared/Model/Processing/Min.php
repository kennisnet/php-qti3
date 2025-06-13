<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;

class Min extends AbstractQtiExpression
{
    /**
     * @param array<int,AbstractQtiExpression> $expressions
     */
    public function __construct(
        private readonly array $expressions
    ) {}

    public function children(): array
    {
        return $this->expressions;
    }

    public function evaluate(ItemState $state): float|int
    {
        if (count($this->expressions) === 0) {
            return 0;
        }

        $values = array_map(
            fn(AbstractQtiExpression $element): float|int => $element->evaluateNumber($state),
            $this->expressions
        );

        return min($values);
    }

    public function getBaseType(ItemState $state): BaseType
    {
        return BaseType::FLOAT;
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::SINGLE;
    }

    public function validate(StringCollection $identifiers): StringCollection
    {
        $errors = new StringCollection();
        foreach ($this->expressions as $expression) {
            $errors = $errors->mergeWith($expression->validate($identifiers));
        }

        return $errors;
    }
}
