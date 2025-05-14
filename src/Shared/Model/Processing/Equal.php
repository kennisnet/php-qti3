<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\State\ItemState;

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

    public function evaluate(ItemState $state): mixed
    {
        $value1 = $this->expression1->evaluateNumber($state);
        $value2 = $this->expression2->evaluateNumber($state);

        return (float) $value1 === (float) $value2;
    }
}
