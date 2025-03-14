<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class Mapping extends QtiElement
{
    /**
     * @param array<int,MapEntry> $entries
     */
    public function __construct(
        public readonly array $entries,
        public readonly ?string $defaultValue = null,
        public readonly ?string $lowerBound = null,
    ) {}

    public function attributes(): array
    {
        return [
            'default-value' => $this->defaultValue,
        ];
    }

    public function children(): array
    {
        return $this->entries;
    }
}
