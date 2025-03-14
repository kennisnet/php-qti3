<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class Sum extends QtiElement implements INumericExpression
{
    /**
     * @param array<int,INumericExpression> $elements
     */
    public function __construct(
        public readonly array $elements
    ) {}

    public function children(): array
    {
        return $this->elements;
    }
}
