<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\State\ItemState;

class Member extends AbstractQtiExpression
{
    public function __construct(
        public readonly AbstractQtiExpression $needle,
        public readonly AbstractQtiExpression $haystack
    ) {}

    public function children(): array
    {
        return [
            $this->needle,
            $this->haystack,
        ];
    }

    public function evaluate(ItemState $state): bool
    {
        $haystackValue = $this->haystack->evaluateArray($state);

        return in_array(
            $this->needle->evaluate($state),
            $haystackValue
        );
    }
}
