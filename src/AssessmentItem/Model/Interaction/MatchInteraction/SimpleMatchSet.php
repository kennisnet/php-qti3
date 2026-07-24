<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\MatchInteraction;

use Qti3\Shared\Model\AbstractBaseSequenceElement;
use Qti3\Shared\Model\BaseSequenceAttributes;

class SimpleMatchSet extends AbstractBaseSequenceElement
{
    /**
     * @param array<int,SimpleAssociableChoice> $choices
     */
    public function __construct(
        public array $choices,
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
        return $this->choices;
    }
}
