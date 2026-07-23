<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\ExtendedTextInteraction;

use Qti3\AssessmentItem\Model\Interaction\AbstractInteractionElement;
use Qti3\Shared\Model\SharedAttributes;
use Qti3\AssessmentItem\Model\Interaction\Prompt;
use Qti3\Shared\Model\QtiElement;

/**
 * The extended text interaction allows a candidate to supply a text string for a response.
 */
class ExtendedTextInteraction extends AbstractInteractionElement
{
    public function __construct(
        public string $responseIdentifier = 'RESPONSE',
        public ?Prompt $prompt = null,
        public ?int $base = null,
        public ?string $stringIdentifier = null,
        public ?int $expectedLength = null,
        public ?string $patternMask = null,
        public ?string $placeholderText = null,
        public ?int $maxStrings = null,
        public ?int $minStrings = null,
        public ?int $expectedLines = null,
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
            'max-strings' => $this->maxStrings === null ? null : (string) $this->maxStrings,
            'min-strings' => $this->minStrings === null ? null : (string) $this->minStrings,
            'expected-lines' => $this->expectedLines === null ? null : (string) $this->expectedLines,
            'format' => $this->format,
            ...$this->sharedAttributes(),
        ];
    }

    /**
     * @return array<int,QtiElement|null>
     */
    public function children(): array
    {
        return [
            $this->prompt,
        ];
    }
}
