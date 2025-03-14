<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\RubricBlock;

use App\SharedKernel\Domain\Qti\Shared\Model\ContentBody;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class RubricBlock extends QtiElement
{
    public function __construct(
        public readonly xUse $use,
        public readonly View $view,
        public readonly ContentBody $contentBody,
        public readonly ?string $class = null
    ) {}

    public function attributes(): array
    {
        return [
            'use' => $this->use->value,
            'view' => $this->view->value,
            'class' => $this->class,
        ];
    }

    public function children(): array
    {
        return [$this->contentBody];
    }
}
