<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\HottextInteraction;

use Qti3\Shared\Model\AbstractBaseSequenceElement;
use Qti3\Shared\Model\BaseSequenceAttributes;
use Qti3\Shared\Model\ContentNodeCollection;

/**
 * The HotTextInteraction.Type (qti-hottext-interaction)
 * presents a set of choices to the candidate represented
 * as selectable runs of text embedded within a surrounding
 * context, such as a simple passage of text.
 */
class HottextInteraction extends AbstractBaseSequenceElement
{
    public function __construct(
        public int $maxChoices,
        public ContentNodeCollection $content,
        public string $responseIdentifier = 'RESPONSE',
        public ?int $minChoices = null,
        BaseSequenceAttributes $attributes = new BaseSequenceAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'max-choices' => (string) $this->maxChoices,
            'min-choices' => $this->minChoices === null ? null : (string) $this->minChoices,
            'response-identifier' => $this->responseIdentifier,
            ...$this->baseSequenceAttributes(),
        ];
    }

    public function children(): array
    {
        return [
            ...$this->content->all(),
        ];
    }
}
