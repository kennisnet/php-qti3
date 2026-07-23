<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\HotspotInteraction;

use Qti3\AssessmentItem\Model\Interaction\AbstractInteractionElement;
use Qti3\Shared\Model\SharedAttributes;
use Qti3\Shared\Model\HTMLTag;

/**
 * The hotspot interaction allows a candidate to supply hotspots on an image for a response.
 */
class HotspotInteraction extends AbstractInteractionElement
{
    /**
     * @param array<int,HotspotChoice> $choices
     */
    public function __construct(
        public HTMLTag $image,
        public array $choices,
        public int $maxChoices,
        public string $responseIdentifier = 'RESPONSE',
        public ?int $minChoices = null,
        SharedAttributes $attributes = new SharedAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'max-choices' => (string) $this->maxChoices,
            'min-choices' => $this->minChoices === null ? null : (string) $this->minChoices,
            'response-identifier' => $this->responseIdentifier,
            ...$this->sharedAttributes(),
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
