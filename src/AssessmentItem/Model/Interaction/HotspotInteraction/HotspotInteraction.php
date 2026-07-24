<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\HotspotInteraction;

use Qti3\Shared\Model\AbstractBaseSequenceElement;
use Qti3\Shared\Model\BaseSequenceAttributes;
use Qti3\Shared\Model\HTMLTag;

/**
 * The hotspot interaction allows a candidate to supply hotspots on an image for a response.
 */
class HotspotInteraction extends AbstractBaseSequenceElement
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
        BaseSequenceAttributes $attributes = new BaseSequenceAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'max-choices' => (string) $this->maxChoices,
            'min-choices' => $this->minChoices === null ? null : (string) $this->minChoices,
            'response-identifier' => $this->responseIdentifier,
            ...$this->baseSequenceAttributes(),
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
