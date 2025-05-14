<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;
use App\SharedKernel\Domain\Qti\State\ItemState;
use InvalidArgumentException;

abstract class AbstractQtiExpression extends QtiElement
{
    abstract public function evaluate(ItemState $state): mixed;

    public function evaluateBoolean(ItemState $state): bool
    {
        $value = $this->evaluate($state);
        if (!is_bool($value)) {
            throw new InvalidArgumentException('Element is not boolean');
        }

        return $value;
    }

    public function evaluateNumber(ItemState $state): float|int
    {
        $value = $this->evaluate($state);
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Element is not numeric');
        }

        return is_string($value) ? (float) $value : $value;
    }

    /**
     * @return array<int, mixed>
     */
    public function evaluateArray(ItemState $state): array
    {
        $value = $this->evaluate($state);
        if (!is_array($value)) {
            throw new InvalidArgumentException('Element is not an array');
        }

        // Ensure the array has integer keys
        $intKeyedResult = [];
        foreach ($value as $item) {
            $intKeyedResult[] = $item;
        }
        return $intKeyedResult;
    }
}
