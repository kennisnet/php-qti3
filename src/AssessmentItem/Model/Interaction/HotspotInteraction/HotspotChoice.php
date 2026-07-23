<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\HotspotInteraction;

use Qti3\AssessmentItem\Model\Interaction\AbstractInteractionElement;
use Qti3\Shared\Model\SharedAttributes;
use Qti3\AssessmentItem\Model\Shape\IShapeWithCoords;

class HotspotChoice extends AbstractInteractionElement
{
    public function __construct(
        public IShapeWithCoords $shape,
        public string $identifier,
        public ?string $templateIdentifier = null,
        public string $showHide = 'show',
        public ?string $hotspotLabel = null,
        SharedAttributes $attributes = new SharedAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'shape' => $this->shape->name()->value,
            'coords' => (string) $this->shape->coords(),
            'identifier' => $this->identifier,
            'template-identifier' => $this->templateIdentifier,
            'show-hide' => $this->showHide,
            'hotspot-label' => $this->hotspotLabel,
            ...$this->sharedAttributes(),
        ];
    }
}
