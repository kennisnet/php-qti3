<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class Lt extends QtiElement implements IBooleanExpression
{
    public function __construct(
        public readonly IQtiExpression $expression1,
        public readonly IQtiExpression $expression2,
    ) {}

    public function children(): array
    {
        return [
            $this->expression1,
            $this->expression2,
        ];
    }
}
