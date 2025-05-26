<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;

class Subtract extends AbstractQtiExpression
{
    public function __construct(
        private readonly AbstractQtiExpression $minuend,
        private readonly AbstractQtiExpression $subtrahend
    ) {}

    public function children(): array
    {
        return [$this->minuend, $this->subtrahend];
    }

    public function evaluate(ItemState $state): float|int
    {
        $minuend = $this->minuend->evaluateNumber($state);
        $subtrahend = $this->subtrahend->evaluateNumber($state);

        return $minuend - $subtrahend;
    }

    public function getBaseType(ItemState $state): BaseType
    {
        return BaseType::FLOAT;
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::SINGLE;
    }
}
