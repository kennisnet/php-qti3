<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class OutcomeProcessing extends QtiElement
{
    /**
     * @param array<int,IOutcomeProcessingElement> $elements
     */
    public function __construct(
        public readonly array $elements
    ) {}

    public function children(): array
    {
        return $this->elements;
    }
}
