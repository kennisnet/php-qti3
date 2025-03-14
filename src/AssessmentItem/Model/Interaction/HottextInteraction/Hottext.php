<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\HottextInteraction;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;
use App\SharedKernel\Domain\Qti\Shared\Model\TextNode;

class Hottext extends QtiElement
{
    public function __construct(
        public string $identifier,
        public TextNode $content,
    ) {}

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
        ];
    }

    public function children(): array
    {
        return  [
            $this->content,
        ];
    }
}
