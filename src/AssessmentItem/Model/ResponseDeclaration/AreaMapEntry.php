<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\IShapeWithCoords;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class AreaMapEntry extends QtiElement
{
    public function __construct(
        public IShapeWithCoords $shape,
        public readonly float $mappedValue,
    ) {}

    public function attributes(): array
    {
        return [
            'shape' => $this->shape->name()->value,
            'coords' => $this->shape->coords(),
            'mapped-value' => (string) $this->mappedValue,
        ];
    }
}
