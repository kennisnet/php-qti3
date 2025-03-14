<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class IsNull extends QtiElement implements IBooleanExpression
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
}
