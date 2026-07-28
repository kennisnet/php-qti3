<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\InlineChoiceInteraction;

use Qti3\Shared\Model\AbstractBaseSequenceElement;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\BaseSequenceAttributes;

/**
 * The optional qti-label of an {@see InlineChoiceInteraction}: a run of inline
 * content that labels the interaction. It carries no attributes of its own
 * beyond the shared ones.
 */
class Label extends AbstractBaseSequenceElement
{
    public function __construct(
        public ContentNodeCollection $content,
        BaseSequenceAttributes $attributes = new BaseSequenceAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            ...$this->baseSequenceAttributes(),
        ];
    }

    public function children(): array
    {
        return $this->content->all();
    }
}
