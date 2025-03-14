<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\TextEntryInteraction;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

/**
 * The text entry interaction allows a candidate to supply a text string for a response.
 */
class TextEntryInteraction extends QtiElement
{
    public function __construct(
        public string $responseIdentifier = 'RESPONSE'
    ) {}

    public function attributes(): array
    {
        return [
            'response-identifier' => $this->responseIdentifier,
        ];
    }
}
