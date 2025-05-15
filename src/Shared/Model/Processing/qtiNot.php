<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\State\ItemState;

class qtiNot extends AbstractQtiExpression
{
    public function __construct(
        private readonly AbstractQtiExpression $expression
    ) {}

    public static function qtiTagName(): string
    {
        return 'qti-not'; // Not is a reserved keyword in PHP
    }

    public function children(): array
    {
        return [$this->expression];
    }

    public function evaluate(ItemState $state): bool
    {
        $value = $this->expression->evaluateBoolean($state);

        return !$value;
    }
}
