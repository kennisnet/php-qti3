<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class MapEntry extends QtiElement
{
    public function __construct(
        public readonly string $mapKey,
        public readonly float $mappedValue,
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

    /**
     * @param array<int,bool|string|int|float>|bool|string|int|float|null $response
     */
    public function evaluate(mixed $response): float
    {
        if (!is_array($response)) {
            $response = [$response];
        }
        if (in_array(
            $this->processKey($this->mapKey),
            array_map([$this, 'processKey'], $response),
        )) {
            return $this->mappedValue;
        }

        return 0;
    }

    private function processKey(string $key): string
    {
        return $this->caseSensitive ? $key : strtolower($key);
    }
}
