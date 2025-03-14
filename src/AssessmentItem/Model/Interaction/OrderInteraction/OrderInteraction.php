<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\OrderInteraction;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\ChoiceInteraction\SimpleChoice;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\Prompt;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

/**
 * The text entry interaction allows a candidate to supply a text string for a response.
 */
class OrderInteraction extends QtiElement
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
    ) {}

    public function attributes(): array
    {
        return [
            'response-identifier' => $this->responseIdentifier,
            'orientation' => $this->orientation->value,
            'shuffle' => $this->shuffle === null ? null : ($this->shuffle ? 'true' : 'false'),
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
