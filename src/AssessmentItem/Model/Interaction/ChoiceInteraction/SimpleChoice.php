<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\ChoiceInteraction;

use Qti3\AssessmentItem\Model\Feedback\FeedbackInline;
use Qti3\Shared\Model\AbstractBaseSequenceElement;
use Qti3\Shared\Model\BaseSequenceAttributes;
use Qti3\Shared\Model\ContentNodeCollection;

class SimpleChoice extends AbstractBaseSequenceElement
{
    public function __construct(
        public string $identifier,
        public ContentNodeCollection $content,
        public ?FeedbackInline $feedbackInline = null,
        public bool $fixed = false,
        public ?string $templateIdentifier = null,
        public string $showHide = 'show',
        BaseSequenceAttributes $attributes = new BaseSequenceAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
            'fixed' => $this->fixed ? 'true' : null,
            'template-identifier' => $this->templateIdentifier,
            'show-hide' => $this->showHide,
            ...$this->baseSequenceAttributes(),
        ];
    }

    public function children(): array
    {
        return [
            ...$this->content->all(),
            $this->feedbackInline,
        ];
    }
}
