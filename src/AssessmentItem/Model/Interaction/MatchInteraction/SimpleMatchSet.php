<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\MatchInteraction;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class SimpleMatchSet extends QtiElement
{
    public function __construct(
        /** @var array<int,SimpleAssociableChoice> */
        public array $choices
    ) {}

    public function children(): array
    {
        return $this->choices;
    }
}
