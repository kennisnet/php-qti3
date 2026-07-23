<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\InlineChoiceInteraction;

use Qti3\Shared\Model\QtiElement;

class InlineChoiceInteraction extends QtiElement
{
    /**
     * @param array<int,InlineChoice> $choices
     */
    public function __construct(
        public array $choices,
        public string $responseIdentifier = 'RESPONSE',
        public bool $shuffle = false,
        public bool $required = false,
    ) {}

    public function attributes(): array
    {
        return [
            'response-identifier' => $this->responseIdentifier,
            'shuffle' => $this->shuffle ? 'true' : 'false',
            'required' => $this->required ? 'true' : null,
        ];
    }

    /**
     * @return array<int,QtiElement>
     */
    public function children(): array
    {
        return [
            ...$this->choices,
        ];
    }
}
