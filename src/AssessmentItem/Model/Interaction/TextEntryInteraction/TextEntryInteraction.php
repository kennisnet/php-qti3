<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\TextEntryInteraction;

use Qti3\AssessmentItem\Model\Interaction\AbstractInteractionElement;
use Qti3\Shared\Model\SharedAttributes;

/**
 * The text entry interaction allows a candidate to supply a text string for a response.
 */
class TextEntryInteraction extends AbstractInteractionElement
{
    public function __construct(
        public string $responseIdentifier = 'RESPONSE',
        public ?int $base = null,
        public ?string $stringIdentifier = null,
        public ?int $expectedLength = null,
        public ?string $patternMask = null,
        public ?string $placeholderText = null,
        public ?string $format = null,
        SharedAttributes $attributes = new SharedAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'response-identifier' => $this->responseIdentifier,
            'base' => $this->base === null ? null : (string) $this->base,
            'string-identifier' => $this->stringIdentifier,
            'expected-length' => $this->expectedLength === null ? null : (string) $this->expectedLength,
            'pattern-mask' => $this->patternMask,
            'placeholder-text' => $this->placeholderText,
            'format' => $this->format,
            ...$this->sharedAttributes(),
        ];
    }
}
