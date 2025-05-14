<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\State\ItemState;

class qtiMatch extends AbstractQtiExpression
{
    public function __construct(
        public readonly AbstractQtiExpression $expression1,
        public readonly AbstractQtiExpression $expression2,
    ) {}

    public static function qtiTagName(): string
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

    public function evaluate(ItemState $state): bool
    {
        return $this->expression1->evaluate($state) === $this->expression2->evaluate($state);
    }
}
