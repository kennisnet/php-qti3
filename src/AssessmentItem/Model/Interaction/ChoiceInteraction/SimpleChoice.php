<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\ChoiceInteraction;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Feedback\FeedbackInline;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class SimpleChoice extends QtiElement
{
    public function __construct(
        public string $identifier,
        public ContentNodeCollection $content,
        public ?FeedbackInline $feedbackInline = null,
    ) {}

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
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
