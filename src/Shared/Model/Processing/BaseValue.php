<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;
use App\SharedKernel\Domain\Qti\Shared\Model\TextNode;

class BaseValue extends QtiElement implements INumericExpression
{
    public function __construct(
        public readonly BaseType $baseType,
        public readonly string|int|float|bool $value
    ) {}

    public function attributes(): array
    {
        return [
            'base-type' => $this->baseType->value,
        ];
    }

    public function children(): array
    {
        return [
            new TextNode(
                is_bool($this->value) ?
                    ($this->value ? 'true' : 'false')
                    : (string) $this->value
            ),
        ];
    }
}
