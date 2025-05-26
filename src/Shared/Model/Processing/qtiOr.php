<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;

class qtiOr extends AbstractQtiExpression
{
    /**
     * @param array<int,AbstractQtiExpression> $elements
     */
    public function __construct(
        public readonly array $elements
    ) {}

    public static function qtiTagName(): string
    {
        return 'qti-or'; // Or is a reserved keyword in PHP
    }

    public function children(): array
    {
        return $this->elements;
    }

    public function evaluate(ItemState $state): bool
    {
        return array_reduce(
            $this->elements,
            function(bool $carry, AbstractQtiExpression $element) use ($state): bool {
                $value = $element->evaluateBoolean($state);

                return $carry || $value;
            },
            true
        );
    }

    public function getBaseType(ItemState $state): BaseType
    {
        return BaseType::BOOLEAN;
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::SINGLE;
    }
}
