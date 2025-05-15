<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\State\ItemState;

class Delete extends AbstractQtiExpression
{
    public function __construct(
        private readonly AbstractQtiExpression $valueToRemove,
        private readonly AbstractQtiExpression $container
    ) {}

    public function children(): array
    {
        return [$this->valueToRemove, $this->container];
    }

    /**
     * @return array<int, mixed>|null
     */
    public function evaluate(ItemState $state): ?array
    {
        $valueToRemove = $this->valueToRemove->evaluate($state);
        $container = $this->container->evaluate($state);

        if ($valueToRemove === null || $container === null || !is_array($container)) {
            return null;
        }

        // Remove all instances of the value from the container
        $result = array_filter($container, function($item) use ($valueToRemove): bool {
            // Use strict comparison for numbers and non-strict for strings
            if (is_numeric($valueToRemove) && is_numeric($item)) {
                return (float) $item !== (float) $valueToRemove;
            }
            return $item != $valueToRemove;
        });

        return array_values($result);
    }
}
