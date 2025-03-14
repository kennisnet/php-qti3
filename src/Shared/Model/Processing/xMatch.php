<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class xMatch extends QtiElement implements IBooleanExpression
{
    public function __construct(
        public readonly IQtiExpression $expression1,
        public readonly IQtiExpression $expression2,
    ) {}

    public function tagName(): string
    {
        return 'qti-match'; // Match is a reserved keyword in PHP
    }

    public function children(): array
    {
        return [
            $this->expression1,
            $this->expression2,
        ];
    }
}
