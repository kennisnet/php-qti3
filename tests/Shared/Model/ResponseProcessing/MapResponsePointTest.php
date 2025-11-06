<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\AreaMapEntry;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\AreaMapping;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclaration;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclarationCollection;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\Circle;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\Coordinate;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\DefaultShape;
use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\MapResponsePoint;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseProcessing;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\Qti\State\OutcomeSet;
use App\SharedKernel\Domain\Qti\State\ResponseSet;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MapResponsePointTest extends TestCase
{
    private MapResponsePoint $mapResponsePoint;

    protected function setUp(): void
    {
        $this->mapResponsePoint = new MapResponsePoint('identifier');
    }

    #[Test]
    public function testMapResponsePoint(): void
    {
        $responseDeclaration = new ResponseDeclaration(BaseType::STRING, Cardinality::SINGLE, 'identifier');
        $responseDeclarations = new ResponseDeclarationCollection([$responseDeclaration]);
        $responseSet = new ResponseSet($responseDeclarations);
        $outcomeSet = new OutcomeSet(new OutcomeDeclarationCollection([]));
        $itemState = new ItemState($responseSet, $outcomeSet, new ResponseProcessing([]));

        $this->assertEquals(
            [
                'identifier' => 'identifier',
            ],
            $this->mapResponsePoint->attributes(),
        );
        $this->assertEquals(BaseType::FLOAT, $this->mapResponsePoint->getBaseType($itemState));
        $this->assertEquals(Cardinality::SINGLE, $this->mapResponsePoint->getCardinality($itemState));
    }

    #[Test]
    public function testEvaluateWithNoAreaMapping(): void
    {
        // Arrange
        $responseDeclaration = new ResponseDeclaration(BaseType::STRING, Cardinality::SINGLE, 'identifier');
        $responseDeclarations = new ResponseDeclarationCollection([$responseDeclaration]);
        $responseSet = new ResponseSet($responseDeclarations);
        $outcomeSet = new OutcomeSet(new OutcomeDeclarationCollection([]));
        $itemState = new ItemState($responseSet, $outcomeSet, new ResponseProcessing([]));

        // Act
        $result = $this->mapResponsePoint->evaluate($itemState);

        // Assert
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function testEvaluateWithNonArrayResponseValue(): void
    {
        // Arrange
        $areaMapping = new AreaMapping([], '0');
        $responseDeclaration = new ResponseDeclaration(
            BaseType::STRING,
            Cardinality::SINGLE,
            'identifier',
            null,
            null,
            $areaMapping,
        );
        $responseDeclarations = new ResponseDeclarationCollection([$responseDeclaration]);
        $responseSet = new ResponseSet($responseDeclarations);
        $responseSet->responses['identifier'] = 'not-an-array';
        $outcomeSet = new OutcomeSet(new OutcomeDeclarationCollection([]));
        $itemState = new ItemState($responseSet, $outcomeSet, new ResponseProcessing([]));

        // Act
        $result = $this->mapResponsePoint->evaluate($itemState);

        // Assert
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function testEvaluateWithNonStringResponsePoint(): void
    {
        // Arrange
        $circle = new Circle(new Coordinate('50'), new Coordinate('50'), new Coordinate('10'));
        $areaMapEntry = new AreaMapEntry($circle, 1.0);
        $areaMapping = new AreaMapping([$areaMapEntry], '0');
        $responseDeclaration = new ResponseDeclaration(
            BaseType::STRING,
            Cardinality::SINGLE,
            'identifier',
            null,
            null,
            $areaMapping,
        );
        $responseDeclarations = new ResponseDeclarationCollection([$responseDeclaration]);
        $responseSet = new ResponseSet($responseDeclarations);
        $responseSet->responses['identifier'] = [123]; // Non-string response point
        $outcomeSet = new OutcomeSet(new OutcomeDeclarationCollection([]));
        $itemState = new ItemState($responseSet, $outcomeSet, new ResponseProcessing([]));

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Response point is not a string');
        $this->mapResponsePoint->evaluate($itemState);
    }

    #[Test]
    public function testEvaluateWithDefaultShape(): void
    {
        // Arrange
        $defaultShape = new DefaultShape();
        $areaMapEntry = new AreaMapEntry($defaultShape, 2.5);
        $areaMapping = new AreaMapping([$areaMapEntry], '0');
        $responseDeclaration = new ResponseDeclaration(
            BaseType::STRING,
            Cardinality::SINGLE,
            'identifier',
            null,
            null,
            $areaMapping,
        );
        $responseDeclarations = new ResponseDeclarationCollection([$responseDeclaration]);
        $responseSet = new ResponseSet($responseDeclarations);
        $responseSet->responses['identifier'] = ['12 34'];
        $outcomeSet = new OutcomeSet(
            new OutcomeDeclarationCollection([]),
        );
        $itemState = new ItemState($responseSet, $outcomeSet, new ResponseProcessing([]));

        // Act
        $result = $this->mapResponsePoint->evaluate($itemState);

        // Assert
        $this->assertEquals(2.5, $result);
    }
}
