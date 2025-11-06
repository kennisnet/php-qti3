<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\State\ItemState;
use RuntimeException;

class IndexExpression
{
    public function __construct(
        public string $value,
    ) {}

    public function evaluate(ItemState $state): int
    {
        if (is_numeric($this->value)) {
            return (int) $this->value;
        }

        // value is identifier
        $value = $state->getValue($this->value);

        if (!is_numeric($value)) {
            throw new RuntimeException('Value is not numeric');
        }

        return (int) $value;
    }
}
