<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\State\ItemState;

class Sum extends AbstractQtiExpression
{
    /**
     * @param array<int,AbstractQtiExpression> $elements
     */
    public function __construct(
        public readonly array $elements
    ) {}

    public function children(): array
    {
        return $this->elements;
    }

    public function evaluate(ItemState $state): int|float
    {
        return array_reduce(
            $this->elements,
            function(int|float $carry, AbstractQtiExpression $element) use ($state): int|float {
                $value = $element->evaluateNumber($state);

                return $carry + $value;
            },
            0
        );
    }
}
