<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model\Feedback;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Feedback\Visibility;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentBody;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class TestFeedback extends QtiElement
{
    public function __construct(
        public string $identifier,
        public string $outcomeIdentifier,
        public readonly ContentBody $contentBody,
        public TestFeedbackAccess $access = TestFeedbackAccess::AT_END,
        public readonly Visibility $showHide = Visibility::SHOW,
    ) {}

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
            'outcome-identifier' => $this->outcomeIdentifier,
            'show-hide' => $this->showHide->value,
            'access' => $this->access->value,
        ];
    }

    public function children(): array
    {
        return [$this->contentBody];
    }
}
