<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction;

use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class Prompt extends QtiElement
{
    public function __construct(
        public ContentNodeCollection $content
    ) {}

    public function children(): array
    {
        return $this->content->all();
    }
}
