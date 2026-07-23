<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\InlineChoiceInteraction;

use Qti3\AssessmentItem\Model\Interaction\AbstractInteractionElement;
use Qti3\Shared\Model\SharedAttributes;
use Qti3\Shared\Model\ContentNodeCollection;

/**
 * A single selectable option (qti-inline-choice) within an
 * {@see InlineChoiceInteraction}.
 */
class InlineChoice extends AbstractInteractionElement
{
    public function __construct(
        public string $identifier,
        public ContentNodeCollection $content,
        public bool $fixed = false,
        // Identifier of a template variable used to control the visibility of the choice.
        public ?string $templateIdentifier = null,
        public string $showHide = 'show',
        SharedAttributes $attributes = new SharedAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
            'fixed' => $this->fixed ? 'true' : null,
            'template-identifier' => $this->templateIdentifier,
            'show-hide' => $this->showHide,
            ...$this->sharedAttributes(),
        ];
    }

    public function children(): array
    {
        return $this->content->all();
    }
}
