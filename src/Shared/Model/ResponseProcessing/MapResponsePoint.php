<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\Circle;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\Coordinate;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\IShapeWithCoords;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\Polygon;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\Rectangle;
use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\AbstractQtiExpression;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;
use Exception;

class MapResponsePoint extends AbstractQtiExpression
{
    public function __construct(
        public readonly string $identifier,
    ) {}

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
        ];
    }

    public function evaluate(ItemState $state): float
    {
        $areaMapping = $state->responseSet->responseDeclarations->getByIdentifier($this->identifier)->areaMapping;

        if (!$areaMapping) {
            return 0;
        }

        $responseValue = $state->responseSet->getResponseValue($this->identifier);
        if (!is_array($responseValue)) {
            return 0;
        }

        $score = 0;
        foreach ($areaMapping->entries as $entry) {
            foreach ($responseValue as $responsePoint) {
                if (!is_string($responsePoint)) {
                    throw new Exception('Response point is not a string');
                }

                if ($this->responseCorrect($responsePoint, $entry->shape)) {
                    $score += $entry->mappedValue;
                    break;
                }
            }
        }

        return $score;
    }

    private function responseCorrect(string $responsePoint, IShapeWithCoords $shape): bool
    {
        [$responseX, $responseY] = array_map('floatval', explode(' ', $responsePoint));

        if ($shape instanceof Rectangle) {
            return $responseX >= $shape->x1->toFloat()
                && $responseX <= $shape->x2->toFloat()
                && $responseY >= $shape->y1->toFloat()
                && $responseY <= $shape->y2->toFloat();
        }

        if ($shape instanceof Circle) {
            $dx = $responseX - $shape->x->toFloat();
            $dy = $responseY - $shape->y->toFloat();
            return ($dx * $dx + $dy * $dy) <= ($shape->r->toFloat() * $shape->r->toFloat());
        }
        if ($shape instanceof Polygon) {
            // Ray-casting algorithm
            $inside = false;

            /** @var array<array{x: Coordinate, y: Coordinate}> $points */
            $points = $shape->xyPairs;
            $count  = count($points);
            for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
                $xi = $points[$i]['x']->toFloat();
                $yi = $points[$i]['y']->toFloat();
                $xj = $points[$j]['x']->toFloat();
                $yj = $points[$j]['y']->toFloat();

                $intersect = (
                    ($yi > $responseY) !== ($yj > $responseY)
                    && $responseX < ($xj - $xi) * ($responseY - $yi) / ($yj - $yi) + $xi
                );

                if ($intersect) {
                    $inside = !$inside;
                }
            }
            return $inside;

        }

        throw new Exception(sprintf('Shape %s not implemented', $shape::class)); // @codeCoverageIgnore
    }

    public function getBaseType(ItemState $state): BaseType
    {
        return BaseType::FLOAT;
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::SINGLE;
    }

    public function validate(ItemState $itemState): StringCollection
    {
        $errors = new StringCollection();

        if (!$itemState->responseSet->responseDeclarations->getIdentifiers()->has($this->identifier)) {
            $errors->add('Identifier ' . $this->identifier . ' not found');
        }

        return $errors;
    }
}
