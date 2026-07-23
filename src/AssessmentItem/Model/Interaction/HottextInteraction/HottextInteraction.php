<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\HottextInteraction;

use Qti3\AssessmentItem\Model\Interaction\AbstractInteractionElement;
use Qti3\Shared\Model\SharedAttributes;
use Qti3\Shared\Model\ContentNodeCollection;

/**
 * The HotTextInteraction.Type (qti-hottext-interaction)
 * presents a set of choices to the candidate represented
 * as selectable runs of text embedded within a surrounding
 * context, such as a simple passage of text.
 */
class HottextInteraction extends AbstractInteractionElement
{
    public function __construct(
        public int $maxChoices,
        public ContentNodeCollection $content,
        public string $responseIdentifier = 'RESPONSE',
        public ?int $minChoices = null,
        SharedAttributes $attributes = new SharedAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'max-choices' => (string) $this->maxChoices,
            'min-choices' => $this->minChoices === null ? null : (string) $this->minChoices,
            'response-identifier' => $this->responseIdentifier,
            ...$this->sharedAttributes(),
        ];
    }

    public function children(): array
    {
        return [
            ...$this->content->all(),
        ];
    }
}
