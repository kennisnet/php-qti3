<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\ChoiceInteraction;

use Qti3\Shared\Model\AbstractBaseSequenceElement;
use Qti3\Shared\Model\BaseSequenceAttributes;
use Qti3\AssessmentItem\Model\Interaction\OrderInteraction\Orientation;
use Qti3\AssessmentItem\Model\Interaction\Prompt;
use Qti3\Shared\Model\QtiElement;

/**
 * The choice interaction allows a candidate to supply a response by selecting one or more choices from a list.
 */
class ChoiceInteraction extends AbstractBaseSequenceElement
{
    /**
     * @param array<int,SimpleChoice> $choices
     */
    public function __construct(
        public array $choices,
        public string $responseIdentifier = 'RESPONSE',
        public ?Prompt $prompt = null,
        public bool $shuffle = false,
        public int $maxChoices = 1,
        public ?int $minChoices = null,
        public ?Orientation $orientation = null,
        BaseSequenceAttributes $attributes = new BaseSequenceAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'response-identifier' => $this->responseIdentifier,
            'shuffle' => $this->shuffle ? 'true' : 'false',
            'max-choices' => (string) $this->maxChoices,
            'min-choices' => $this->minChoices === null ? null : (string) $this->minChoices,
            'orientation' => $this->orientation?->value,
            ...$this->baseSequenceAttributes(),
        ];
    }

    /**
     * @return array<int,QtiElement|null>
     */
    public function children(): array
    {
        return [
            $this->prompt,
            ...$this->choices,
        ];
    }
}
