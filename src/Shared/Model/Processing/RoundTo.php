<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\State\ItemState;

class RoundTo extends AbstractQtiExpression
{
    public function __construct(
        private readonly AbstractQtiExpression $expression,
        private readonly AbstractQtiExpression $decimals,
        private readonly string $roundingMode = 'nearest'
    ) {}

    public function children(): array
    {
        return [$this->expression, $this->decimals];
    }

    public function evaluate(ItemState $state): float
    {
        $value = $this->expression->evaluateNumber($state);
        $decimals = (int) $this->decimals->evaluateNumber($state);

        // Clamp decimals to a reasonable range
        $decimals = max(0, min(15, $decimals));

        // Apply the appropriate rounding function based on the mode
        return match ($this->roundingMode) {
            'floor' => $this->floorTo($value, $decimals),
            'ceiling' => $this->ceilTo($value, $decimals),
            default => round($value, $decimals),
        };
    }

    private function floorTo(float $value, int $decimals): float
    {
        if ($decimals === 0) {
            return floor($value);
        }

        $multiplier = 10 ** $decimals;
        return floor($value * $multiplier) / $multiplier;
    }

    private function ceilTo(float $value, int $decimals): float
    {
        if ($decimals === 0) {
            return ceil($value);
        }

        $multiplier = 10 ** $decimals;
        return ceil($value * $multiplier) / $multiplier;
    }
}
