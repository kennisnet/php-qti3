<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\InlineChoiceInteraction;

use Qti3\AssessmentItem\Model\Interaction\AbstractInteractionElement;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\SharedAttributes;

/**
 * The optional qti-label of an {@see InlineChoiceInteraction}: a run of inline
 * content that labels the interaction. It carries no attributes of its own
 * beyond the shared ones.
 */
class Label extends AbstractInteractionElement
{
    public function __construct(
        public ContentNodeCollection $content,
        SharedAttributes $attributes = new SharedAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            ...$this->sharedAttributes(),
        ];
    }

    public function children(): array
    {
        return $this->content->all();
    }
}
