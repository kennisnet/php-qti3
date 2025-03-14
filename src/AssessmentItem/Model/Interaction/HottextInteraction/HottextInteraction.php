<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\HottextInteraction;

use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

/**
 * The HotTextInteraction.Type (qti-hottext-interaction)
 * presents a set of choices to the candidate represented
 * as selectable runs of text embedded within a surrounding
 * context, such as a simple passage of text.
 */
class HottextInteraction extends QtiElement
{
    public function __construct(
        public int $maxChoices,
        public ContentNodeCollection $content,
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
            ...$this->content->all(),
        ];
    }
}
