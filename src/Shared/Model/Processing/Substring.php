<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;

class Substring extends AbstractQtiExpression
{
    public function __construct(
        private readonly AbstractQtiExpression $haystack,
        private readonly AbstractQtiExpression $needle,
        public readonly ?bool $caseSensitive = null,
    ) {}

    public function children(): array
    {
        return [$this->haystack, $this->needle];
    }

    public function attributes(): array
    {
        return [
            'case-sensitive' => $this->caseSensitive === null ? null : ($this->caseSensitive ? 'true' : 'false'),
        ];
    }

    public function evaluate(ItemState $state): bool
    {
        $haystack = $this->haystack->evaluateString($state);
        $needle = $this->needle->evaluateString($state);

        if ($this->caseSensitive || $this->caseSensitive === null) {
            return str_contains($haystack, $needle);
        } else {
            return str_contains(strtolower($haystack), strtolower($needle));
        }
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
