<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\SelectPointInteraction;

use Qti3\Shared\Model\AbstractSharedAttributeElement;
use Qti3\Shared\Model\SharedAttributes;
use Qti3\AssessmentItem\Model\Interaction\Prompt;
use Qti3\Shared\Model\HTMLTag;

/**
 * The select point interaction requires the candidate to select points on an image.
 */
class SelectPointInteraction extends AbstractSharedAttributeElement
{
    public function __construct(
        public HTMLTag $image,
        public int $maxChoices,
        public ?Prompt $prompt = null,
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
            $this->prompt,
            $this->image,
        ];
    }
}
