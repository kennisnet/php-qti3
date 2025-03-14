<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\ExtendedTextInteraction;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\Prompt;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

/**
 * The extended text interaction allows a candidate to supply a text string for a response.
 */
class ExtendedTextInteraction extends QtiElement
{
    public function __construct(
        public string $responseIdentifier = 'RESPONSE',
        public ?Prompt $prompt = null,
    ) {}

    public function attributes(): array
    {
        return [
            'response-identifier' => $this->responseIdentifier,
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
