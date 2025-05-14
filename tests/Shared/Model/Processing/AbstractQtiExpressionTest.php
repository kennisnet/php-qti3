<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclaration;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclarationCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Member;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Multiple;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\qtiAnd;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Sum;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseProcessing;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\Qti\State\OutcomeSet;
use App\SharedKernel\Domain\Qti\State\ResponseSet;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractQtiExpressionTest extends TestCase
{
    private function createItemState(): ItemState
    {
        return new ItemState(
            new ResponseSet(new ResponseDeclarationCollection([
                new ResponseDeclaration(BaseType::STRING, Cardinality::SINGLE, 'identifier'),
            ])),
            new OutcomeSet(new OutcomeDeclarationCollection([])),
            new ResponseProcessing([])
        );
    }

    #[Test]
    public function evaluateBooleanReturnsCorrectValue(): void
    {
        // Arrange
        $itemState = $this->createItemState();
        $expression = new qtiAnd([
            new BaseValue(BaseType::BOOLEAN, true),
            new BaseValue(BaseType::BOOLEAN, true),
        ]);

        // Act
        $result = $expression->evaluateBoolean($itemState);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function evaluateNumberReturnsCorrectValue(): void
    {
        // Arrange
        $itemState = $this->createItemState();
        $expression = new Sum([
            new BaseValue(BaseType::INTEGER, 20),
            new BaseValue(BaseType::INTEGER, 22),
        ]);

        // Act
        $result = $expression->evaluateNumber($itemState);

        // Assert
        $this->assertSame(42, $result);
    }

    #[Test]
    public function evaluateArrayReturnsCorrectValue(): void
    {
        // Arrange
        $itemState = $this->createItemState();
        $expression = new Multiple([
            new BaseValue(BaseType::STRING, 'test'),
        ]);

        // Act
        $result = $expression->evaluateArray($itemState);

        // Assert
        $this->assertSame(['test'], $result);
    }
    #[Test]
    public function evaluateWithNonNumericTypeResultsInException(): void
    {
        // Arrange
        $itemState = $this->createItemState();
        $sum = new Sum([
            new BaseValue(BaseType::STRING, 'value'),
        ]);

        // Act & Assert

        $this->expectExceptionMessage('Element is not numeric');

        $sum->evaluate($itemState);
    }

    #[Test]
    public function evaluateWithNonBooleanTypeResultsInException(): void
    {
        // Arrange
        $itemState = $this->createItemState();
        $and = new qtiAnd([
            new BaseValue(BaseType::STRING, 'value'),
        ]);

        // Act & Assert

        $this->expectExceptionMessage('Element is not boolean');

        $and->evaluate($itemState);
    }
    #[Test]
    public function evaluateWithNonArrayTypeResultsInException(): void
    {
        // Arrange
        $itemState = $this->createItemState();
        $member = new Member(
            new BaseValue(BaseType::STRING, 'value'),
            new BaseValue(BaseType::STRING, 'value'),
        );

        // Act & Assert

        $this->expectExceptionMessage('Element is not an array');

        $member->evaluate($itemState);
    }
}
