<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\HotspotInteraction;

use App\SharedKernel\Domain\Qti\Shared\Model\HTMLTag;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

/**
 * The hotspot interaction allows a candidate to supply hotspots on an image for a response.
 */
class HotspotInteraction extends QtiElement
{
    /**
     * @param array<int,HotspotChoice> $choices
     */
    public function __construct(
        public HTMLTag $image,
        public array $choices,
        public int $maxChoices,
        public string $responseIdentifier = 'RESPONSE',
    ) {}

    public function attributes(): array
    {
        return [
            'max-choices' => (string) $this->maxChoices,
            'response-identifier' => $this->responseIdentifier,
        ];
    }

    public function children(): array
    {
        return [
            $this->image,
            ...$this->choices,
        ];
    }
}
