<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape;

class DefaultShape implements IShapeWithCoords
{
    public function name(): ShapeName
    {
        return ShapeName::DEFAULT;
    }

    public function coords(): string
    {
        return '';
    }
}
