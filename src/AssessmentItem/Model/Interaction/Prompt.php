<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction;

use Qti3\Shared\Model\AbstractBaseSequenceElement;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\BaseSequenceAttributes;

class Prompt extends AbstractBaseSequenceElement
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
