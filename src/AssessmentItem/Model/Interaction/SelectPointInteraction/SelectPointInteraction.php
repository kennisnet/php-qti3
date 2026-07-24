<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\SelectPointInteraction;

use Qti3\Shared\Model\AbstractBaseSequenceElement;
use Qti3\Shared\Model\BaseSequenceAttributes;
use Qti3\AssessmentItem\Model\Interaction\Prompt;
use Qti3\Shared\Model\HTMLTag;

/**
 * The select point interaction requires the candidate to select points on an image.
 */
class SelectPointInteraction extends AbstractBaseSequenceElement
{
    public function __construct(
        public HTMLTag $image,
        public int $maxChoices,
        public ?Prompt $prompt = null,
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
            $this->prompt,
            $this->image,
        ];
    }
}
