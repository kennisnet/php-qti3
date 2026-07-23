<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction;

use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\SharedAttributes;

class Prompt extends AbstractInteractionElement
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
