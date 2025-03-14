<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\SelectPointInteraction;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\Prompt;
use App\SharedKernel\Domain\Qti\Shared\Model\HTMLTag;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

/**
 * The select point interaction requires the candidate to select points on an image.
 */
class SelectPointInteraction extends QtiElement
{
    public function __construct(
        public HTMLTag $image,
        public int $maxChoices,
        public ?Prompt $prompt = null,
        public string $responseIdentifier = 'RESPONSE',
    ) {}

    public function attributes(): array
    {
        return [
            'max-choices' => (string) $this->maxChoices,
            'response-identifier' => $this->responseIdentifier,
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
