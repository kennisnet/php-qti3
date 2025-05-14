<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\State\ItemState;

class IsNull extends AbstractQtiExpression
{
    public function __construct(
        public readonly Variable $variable
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
}
