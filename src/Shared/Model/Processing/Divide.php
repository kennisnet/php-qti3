<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class Divide extends QtiElement implements INumericExpression
{
    public function __construct(
        public readonly INumericExpression $element1,
        public readonly INumericExpression $element2
    ) {}

    public function children(): array
    {
        return [
            $this->element1,
            $this->element2,
        ];
    }
}
