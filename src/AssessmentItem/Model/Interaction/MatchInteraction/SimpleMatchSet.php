<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\MatchInteraction;

use Qti3\Shared\Model\AbstractSharedAttributeElement;
use Qti3\Shared\Model\SharedAttributes;

class SimpleMatchSet extends AbstractSharedAttributeElement
{
    /**
     * @param array<int,SimpleAssociableChoice> $choices
     */
    public function __construct(
        public array $choices,
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
        return $this->choices;
    }
}
