<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\OrderInteraction;

use Qti3\AssessmentItem\Model\Interaction\AbstractInteractionElement;
use Qti3\AssessmentItem\Model\Interaction\ChoiceInteraction\SimpleChoice;
use Qti3\Shared\Model\SharedAttributes;
use Qti3\AssessmentItem\Model\Interaction\Prompt;

/**
 * The order interaction requires the candidate to reorder a set of choices.
 */
class OrderInteraction extends AbstractInteractionElement
{
    /**
     * @param array<int,SimpleChoice> $choices
     */
    public function __construct(
        public array $choices,
        public string $responseIdentifier = 'RESPONSE',
        public Orientation $orientation = Orientation::VERTICAL,
        public ?bool $shuffle = null,
        public ?Prompt $prompt = null,
        public ?int $minChoices = null,
        public ?int $maxChoices = null,
        SharedAttributes $attributes = new SharedAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'response-identifier' => $this->responseIdentifier,
            'orientation' => $this->orientation->value,
            'shuffle' => $this->shuffle === null ? null : ($this->shuffle ? 'true' : 'false'),
            'min-choices' => $this->minChoices === null ? null : (string) $this->minChoices,
            'max-choices' => $this->maxChoices === null ? null : (string) $this->maxChoices,
            ...$this->sharedAttributes(),
        ];
    }

    public function children(): array
    {
        return [
            $this->prompt,
            ...$this->choices,
        ];
    }
}
