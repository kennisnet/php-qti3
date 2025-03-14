<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\HotspotInteraction;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\IShapeWithCoords;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class HotspotChoice extends QtiElement
{
    public function __construct(
        public IShapeWithCoords $shape,
        public string $identifier,
    ) {}

    public function attributes(): array
    {
        return [
            'shape' => $this->shape->name()->value,
            'coords' => (string) $this->shape->coords(),
            'identifier' => $this->identifier,
        ];
    }
}
