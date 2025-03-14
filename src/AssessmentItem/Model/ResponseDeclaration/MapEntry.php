<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class MapEntry extends QtiElement
{
    public function __construct(
        public readonly string $mapKey,
        public readonly string|int|float $mappedValue,
        public readonly ?bool $caseSensitive = null,
    ) {}

    public function attributes(): array
    {
        return [
            'map-key' => $this->mapKey,
            'mapped-value' => (string) $this->mappedValue,
            'case-sensitive' => $this->caseSensitive === null ? null : ($this->caseSensitive ? 'true' : 'false'),
        ];
    }
}
