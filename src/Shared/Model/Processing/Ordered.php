<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;

class Ordered extends AbstractQtiExpression
{
    /**
     * @param array<int,AbstractQtiExpression> $elements
     */
    public function __construct(
        public readonly array $elements
    ) {}

    public function children(): array
    {
        return $this->elements;
    }

    /**
     * @return array<int,mixed>
     */
    public function evaluate(ItemState $state): array
    {
        $result = [];

        foreach ($this->elements as $element) {
            $elementValue = $element->evaluate($state);

            if (is_array($elementValue)) {
                $result = array_merge($result, $elementValue);
            } else {
                $result[] = $elementValue;
            }
        }

        // Ensure the array has integer keys
        $intKeyedResult = [];
        foreach ($result as $value) {
            $intKeyedResult[] = $value;
        }
        return $intKeyedResult;
    }

    public function getBaseType(ItemState $state): BaseType
    {
        if (count($this->elements) === 0) {
            return BaseType::STRING;
        }
        return $this->elements[0]->getBaseType($state);
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::ORDERED;
    }
}
