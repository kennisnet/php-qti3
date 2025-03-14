<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class xAnd extends QtiElement implements IBooleanExpression
{
    /**
     * @param array<int,IQtiExpression> $elements
     */
    public function __construct(
        public readonly array $elements
    ) {}

    public function tagName(): string
    {
        return 'qti-and'; // And is a reserved keyword in PHP
    }

    public function children(): array
    {
        return $this->elements;
    }
}
