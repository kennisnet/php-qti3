<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape;

interface IShapeWithCoords
{
    public function name(): ShapeName;

    public function coords(): string;
}
