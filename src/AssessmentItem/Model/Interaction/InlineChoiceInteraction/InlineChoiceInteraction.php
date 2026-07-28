<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\InlineChoiceInteraction;

use Qti3\Shared\Model\AbstractBaseSequenceElement;
use Qti3\Shared\Model\BaseSequenceAttributes;
use Qti3\Shared\Model\QtiElement;

/**
 * The inline choice interaction (qti-inline-choice-interaction) presents a set
 * of choices to the candidate inline, embedded in a surrounding run of content,
 * from which a single choice must be selected (typically rendered as a drop-down).
 */
class InlineChoiceInteraction extends AbstractBaseSequenceElement
{
    /**
     * @param array<int,InlineChoice> $choices
     */
    public function __construct(
        public array $choices,
        public string $responseIdentifier = 'RESPONSE',
        public bool $shuffle = false,
        public bool $required = false,
        public ?int $minChoices = null,
        // The qti-label child element, distinct from the shared `label` attribute.
        public ?Label $labelElement = null,
        BaseSequenceAttributes $attributes = new BaseSequenceAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'response-identifier' => $this->responseIdentifier,
            'shuffle' => $this->shuffle ? 'true' : 'false',
            'required' => $this->required ? 'true' : null,
            'min-choices' => $this->minChoices === null ? null : (string) $this->minChoices,
            ...$this->baseSequenceAttributes(),
        ];
    }

    /**
     * @return array<int,QtiElement|null>
     */
    public function children(): array
    {
        return [
            $this->labelElement,
            ...$this->choices,
        ];
    }
}
