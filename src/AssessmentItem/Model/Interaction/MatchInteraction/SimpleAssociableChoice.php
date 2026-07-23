<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\MatchInteraction;

use Qti3\AssessmentItem\Model\Interaction\AbstractInteractionElement;
use Qti3\Shared\Model\SharedAttributes;
use Qti3\Shared\Model\ContentNodeCollection;

class SimpleAssociableChoice extends AbstractInteractionElement
{
    public function __construct(
        public string $identifier,
        public ContentNodeCollection $content,
        public int $matchMax = 1,
        public ?int $matchMin = null,
        public bool $fixed = false,
        public ?string $templateIdentifier = null,
        public ?string $showHide = null,
        public ?string $matchGroup = null,
        SharedAttributes $attributes = new SharedAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
            'match-max' => (string) $this->matchMax,
            'match-min' => $this->matchMin === null ? null : (string) $this->matchMin,
            'fixed' => $this->fixed ? 'true' : null,
            'template-identifier' => $this->templateIdentifier,
            'show-hide' => $this->showHide,
            'match-group' => $this->matchGroup,
            ...$this->sharedAttributes(),
        ];
    }

    public function children(): array
    {
        return $this->content->all();
    }
}
