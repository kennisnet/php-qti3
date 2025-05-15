<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\State\ItemState;

class Max extends AbstractQtiExpression
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

        return max($values);
    }
}
