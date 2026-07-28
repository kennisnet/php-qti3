<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\GapMatchInteraction;

use Qti3\Shared\Model\AbstractBaseSequenceElement;
use Qti3\Shared\Model\BaseSequenceAttributes;

final class Gap extends AbstractBaseSequenceElement
{
    public function __construct(
        public readonly string $identifier,
        public readonly ?string $templateIdentifier = null,
        public readonly string $showHide = 'show',
        public readonly ?string $matchGroup = null,
        public readonly bool $required = false,
        BaseSequenceAttributes $attributes = new BaseSequenceAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
            'template-identifier' => $this->templateIdentifier,
            'show-hide' => $this->showHide,
            'match-group' => $this->matchGroup,
            'required' => $this->required ? 'true' : null,
            ...$this->baseSequenceAttributes(),
        ];
    }
}
