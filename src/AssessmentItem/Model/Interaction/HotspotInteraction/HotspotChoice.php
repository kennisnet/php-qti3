<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\HotspotInteraction;

use Qti3\Shared\Model\AbstractBaseSequenceElement;
use Qti3\Shared\Model\BaseSequenceAttributes;
use Qti3\AssessmentItem\Model\Shape\IShapeWithCoords;

class HotspotChoice extends AbstractBaseSequenceElement
{
    public function __construct(
        public IShapeWithCoords $shape,
        public string $identifier,
        public ?string $templateIdentifier = null,
        public string $showHide = 'show',
        public ?string $hotspotLabel = null,
        BaseSequenceAttributes $attributes = new BaseSequenceAttributes(),
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
            ...$this->baseSequenceAttributes(),
        ];
    }
}
