<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclaration;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclarationCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Contains;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Delete;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Index;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IndexExpression;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IntegerDivide;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IntegerModulus;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Max;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Member;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Min;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Multiple;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Power;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\qtiAnd;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\qtiNot;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Round;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\RoundTo;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Substring;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Subtract;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Sum;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseProcessing;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\Qti\State\OutcomeSet;
use App\SharedKernel\Domain\Qti\State\ResponseSet;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractQtiExpressionTest extends TestCase
{
    private ItemState $itemState;

    protected function setUp(): void
    {
        $responseDeclarations = new ResponseDeclarationCollection([
            new ResponseDeclaration(
                BaseType::INTEGER,
                Cardinality::SINGLE,
                'RESPONSE'
            ),
            new ResponseDeclaration(
                BaseType::STRING,
                Cardinality::SINGLE,
                'RESPONSE2'
            ),
            new ResponseDeclaration(BaseType::STRING, Cardinality::SINGLE, 'identifier'),
        ]);
        $outcomeDeclarations = new OutcomeDeclarationCollection([]);

        // Create a simple ResponseProcessing instance with no elements
        $responseProcessing = new ResponseProcessing([]);

        $this->itemState = new ItemState(
            new ResponseSet($responseDeclarations),
            new OutcomeSet($outcomeDeclarations),
            $responseProcessing,
            false
        );
    }

    #[Test]
    public function evaluateBooleanReturnsCorrectValue(): void
    {
        // Arrange
        $expression = new qtiAnd([
            new BaseValue(BaseType::BOOLEAN, true),
            new BaseValue(BaseType::BOOLEAN, true),
        ]);

        // Act
        $result = $expression->evaluateBoolean($this->itemState);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function evaluateNumberReturnsCorrectValue(): void
    {
        // Arrange
        $expression = new Sum([
            new BaseValue(BaseType::INTEGER, 20),
            new BaseValue(BaseType::INTEGER, 22),
        ]);

        // Act
        $result = $expression->evaluateNumber($this->itemState);

        // Assert
        $this->assertSame(42, $result);
    }

    #[Test]
    public function evaluateArrayReturnsCorrectValue(): void
    {
        // Arrange
        $expression = new Multiple([
            new BaseValue(BaseType::STRING, 'test'),
        ]);

        // Act
        $result = $expression->evaluateArray($this->itemState);

        // Assert
        $this->assertSame(['test'], $result);
    }
    #[Test]
    public function evaluateWithNonNumericTypeResultsInException(): void
    {
        // Arrange
        $sum = new Sum([
            new BaseValue(BaseType::STRING, 'value'),
        ]);

        // Act & Assert

        $this->expectExceptionMessage('Element is not numeric');

        $sum->evaluate($this->itemState);
    }

    #[Test]
    public function evaluateWithNonBooleanTypeResultsInException(): void
    {
        // Arrange
        $and = new qtiAnd([
            new BaseValue(BaseType::STRING, 'value'),
        ]);

        // Act & Assert

        $this->expectExceptionMessage('Element is not boolean');

        $and->evaluate($this->itemState);
    }
    #[Test]
    public function evaluateWithNonArrayTypeResultsInException(): void
    {
        // Arrange
        $member = new Member(
            new BaseValue(BaseType::STRING, 'value'),
            new BaseValue(BaseType::STRING, 'value'),
        );

        // Act & Assert

        $this->expectExceptionMessage('Element is not an array');

        $member->evaluate($this->itemState);
    }

    #[Test]
    public function testQtiNot(): void
    {
        $expression = new qtiNot(new BaseValue(BaseType::BOOLEAN, 'true'));
        $this->assertFalse($expression->evaluate($this->itemState));
        $this->assertCount(1, $expression->children());

        $expression = new qtiNot(new BaseValue(BaseType::BOOLEAN, 'false'));
        $this->assertTrue($expression->evaluate($this->itemState));
    }

    #[Test]
    public function testContains(): void
    {
        $multiple = new Multiple([
            new BaseValue(BaseType::STRING, 'apple'),
            new BaseValue(BaseType::STRING, 'banana'),
            new BaseValue(BaseType::STRING, 'cherry'),
        ]);

        $expression = new Contains($multiple, new Multiple([new BaseValue(BaseType::STRING, 'banana')]));
        $this->assertTrue($expression->evaluate($this->itemState));
        $this->assertCount(2, $expression->children());

        $expression = new Contains($multiple, new Multiple([new BaseValue(BaseType::STRING, 'grape')]));
        $this->assertFalse($expression->evaluate($this->itemState));
    }

    #[Test]
    public function testSubstring(): void
    {
        $expression = new Substring(
            new BaseValue(BaseType::STRING, 'Hello World'),
            new BaseValue(BaseType::STRING, 'World')
        );
        $this->assertTrue($expression->evaluate($this->itemState));
        $this->assertCount(2, $expression->children());
        $this->assertCount(1, $expression->attributes());

        $expression = new Substring(
            new BaseValue(BaseType::STRING, 'Hello World'),
            new BaseValue(BaseType::STRING, 'WORLD'),
            false
        );
        $this->assertTrue($expression->evaluate($this->itemState));

        $expression = new Substring(
            new BaseValue(BaseType::STRING, 'Hello World'),
            new BaseValue(BaseType::STRING, 'WORLD')
        );
        $this->assertFalse($expression->evaluate($this->itemState));

        $this->expectExceptionMessage('Element is not a string');
        $expression = new Substring(
            new BaseValue(BaseType::INTEGER, 1),
            new BaseValue(BaseType::STRING, 'WORLD')
        );
        $expression->evaluate($this->itemState);
    }

    #[Test]
    public function testSubtract(): void
    {
        $expression = new Subtract(
            new BaseValue(BaseType::FLOAT, '10.5'),
            new BaseValue(BaseType::FLOAT, '5.2')
        );
        $this->assertEquals(5.3, $expression->evaluate($this->itemState));
        $this->assertCount(2, $expression->children());

        $expression = new Subtract(
            new BaseValue(BaseType::INTEGER, '10'),
            new BaseValue(BaseType::INTEGER, '5')
        );
        $this->assertEquals(5, $expression->evaluate($this->itemState));
    }

    #[Test]
    public function testPower(): void
    {
        $expression = new Power(
            new BaseValue(BaseType::FLOAT, '2'),
            new BaseValue(BaseType::FLOAT, '3')
        );
        $this->assertEquals(8, $expression->evaluate($this->itemState));
        $this->assertCount(2, $expression->children());

        $expression = new Power(
            new BaseValue(BaseType::FLOAT, '2'),
            new BaseValue(BaseType::FLOAT, '0.5')
        );
        $this->assertEquals(sqrt(2), $expression->evaluate($this->itemState));
    }

    #[Test]
    public function testRound(): void
    {
        $expression = new Round(
            new BaseValue(BaseType::FLOAT, '3.7')
        );
        $this->assertEquals(4, $expression->evaluate($this->itemState));
        $this->assertCount(1, $expression->children());

        $expression = new Round(
            new BaseValue(BaseType::FLOAT, '3.2')
        );
        $this->assertEquals(3, $expression->evaluate($this->itemState));

        $expression = new Round(
            new BaseValue(BaseType::FLOAT, '3.7'),
            'floor'
        );
        $this->assertEquals(3, $expression->evaluate($this->itemState));

        $expression = new Round(
            new BaseValue(BaseType::FLOAT, '3.2'),
            'ceiling'
        );
        $this->assertEquals(4, $expression->evaluate($this->itemState));
    }

    public function testRoundTo(): void
    {
        // Test rounding to 2 decimal places
        $expression = new RoundTo(
            new BaseValue(BaseType::FLOAT, '3.14159'),
            new BaseValue(BaseType::INTEGER, '2')
        );
        $this->assertEquals(3.14, $expression->evaluate($this->itemState));
        $this->assertCount(2, $expression->children());

        // Test rounding up to 1 decimal place
        $expression = new RoundTo(
            new BaseValue(BaseType::FLOAT, '3.85'),
            new BaseValue(BaseType::INTEGER, '1')
        );
        $this->assertEquals(3.9, $expression->evaluate($this->itemState));

        // Test with floor rounding mode
        $expression = new RoundTo(
            new BaseValue(BaseType::FLOAT, '3.99'),
            new BaseValue(BaseType::INTEGER, '1'),
            'floor'
        );
        $this->assertEquals(3.9, $expression->evaluate($this->itemState));

        // Test with ceiling rounding mode
        $expression = new RoundTo(
            new BaseValue(BaseType::FLOAT, '3.01'),
            new BaseValue(BaseType::INTEGER, '1'),
            'ceiling'
        );
        $this->assertEquals(3.1, $expression->evaluate($this->itemState));

        // Test rounding to 0 decimal places (should be same as regular round)
        $expression = new RoundTo(
            new BaseValue(BaseType::FLOAT, '3.7'),
            new BaseValue(BaseType::INTEGER, '0')
        );
        $this->assertEquals(4.0, $expression->evaluate($this->itemState));

        $expression = new RoundTo(
            new BaseValue(BaseType::FLOAT, '3.7'),
            new BaseValue(BaseType::INTEGER, '0'),
            'floor'
        );
        $this->assertEquals(3.0, $expression->evaluate($this->itemState));

        $expression = new RoundTo(
            new BaseValue(BaseType::FLOAT, '3.7'),
            new BaseValue(BaseType::INTEGER, '0'),
            'ceiling'
        );
        $this->assertEquals(4.0, $expression->evaluate($this->itemState));
    }

    public function testIntegerDivide(): void
    {
        $expression = new IntegerDivide(
            new BaseValue(BaseType::INTEGER, '10'),
            new BaseValue(BaseType::INTEGER, '3')
        );
        $this->assertEquals(3, $expression->evaluate($this->itemState));

        $expression = new IntegerDivide(
            new BaseValue(BaseType::INTEGER, '10'),
            new BaseValue(BaseType::INTEGER, '0')
        );
        $this->assertEquals(0, $expression->evaluate($this->itemState));
        $this->assertCount(2, $expression->children());
    }

    public function testIntegerModulus(): void
    {
        $expression = new IntegerModulus(
            new BaseValue(BaseType::INTEGER, '10'),
            new BaseValue(BaseType::INTEGER, '3')
        );
        $this->assertEquals(1, $expression->evaluate($this->itemState));
        $this->assertCount(2, $expression->children());

        $expression = new IntegerModulus(
            new BaseValue(BaseType::INTEGER, '10'),
            new BaseValue(BaseType::INTEGER, '0')
        );
        $this->assertEquals(0, $expression->evaluate($this->itemState));
    }

    public function testMin(): void
    {
        $expression = new Min([
            new BaseValue(BaseType::INTEGER, '5'),
            new BaseValue(BaseType::INTEGER, '3'),
            new BaseValue(BaseType::INTEGER, '8'),
        ]);
        $this->assertEquals(3, $expression->evaluate($this->itemState));
        $this->assertCount(3, $expression->children());

        $expression = new Min([]);
        $this->assertEquals(0, $expression->evaluate($this->itemState));
    }

    public function testMax(): void
    {
        $expression = new Max([
            new BaseValue(BaseType::INTEGER, '5'),
            new BaseValue(BaseType::INTEGER, '3'),
            new BaseValue(BaseType::INTEGER, '8'),
        ]);
        $this->assertEquals(8, $expression->evaluate($this->itemState));
        $this->assertCount(3, $expression->children());

        $expression = new Max([]);
        $this->assertEquals(0, $expression->evaluate($this->itemState));
    }

    public function testIndex(): void
    {
        $multiple = new Multiple([
            new BaseValue(BaseType::STRING, 'apple'),
            new BaseValue(BaseType::STRING, 'banana'),
            new BaseValue(BaseType::STRING, 'cherry'),
        ]);

        $expression = new Index($multiple, new IndexExpression('2'));
        $this->assertEquals('banana', $expression->evaluate($this->itemState));
        $this->assertCount(1, $expression->children());
        $this->assertCount(1, $expression->attributes());

        $expression = new Index($multiple, new IndexExpression('4'));
        $this->assertNull($expression->evaluate($this->itemState));

        $this->itemState->responseSet->setResponses(['RESPONSE' => 2]);
        $expression = new Index($multiple, new IndexExpression('RESPONSE'));
        $this->assertEquals('banana', $expression->evaluate($this->itemState));

        $this->itemState->responseSet->setResponses(['RESPONSE2' => 'A']);
        $expression = new Index($multiple, new IndexExpression('RESPONSE2'));

        $this->expectExceptionMessage('Value is not numeric');
        $expression->evaluate($this->itemState);
    }

    #[Test]
    public function deleteRemovesElement(): void
    {
        // Arrange

        $multiple = new Multiple([
            new BaseValue(BaseType::STRING, 'apple'),
            new BaseValue(BaseType::STRING, 'banana'),
            new BaseValue(BaseType::STRING, 'cherry'),
        ]);
        $expression = new Delete(new BaseValue(BaseType::STRING, 'banana'), $multiple);
        $this->assertCount(2, $expression->children());

        // Act

        $result = $expression->evaluate($this->itemState);

        // Assert

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('apple', $result[0]);
        $this->assertEquals('cherry', $result[1]);
    }

    #[Test]
    public function deleteRemovesNumericElement(): void
    {
        // Arrange

        $multiple = new Multiple([
            new BaseValue(BaseType::FLOAT, 1.0),
            new BaseValue(BaseType::FLOAT, 1.1),
            new BaseValue(BaseType::FLOAT, 1.2),
        ]);
        $expression = new Delete(new BaseValue(BaseType::INTEGER, 1), $multiple);

        // Act

        $result = $expression->evaluate($this->itemState);

        // Assert

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(1.1, $result[0]);
        $this->assertEquals(1.2, $result[1]);
    }

    #[Test]
    public function deleteWithSingleResultsInNull(): void
    {
        // Arrange

        $multiple = new BaseValue(BaseType::STRING, 'apple');
        $expression = new Delete(new BaseValue(BaseType::STRING, 'banana'), $multiple);

        // Act

        $result = $expression->evaluate($this->itemState);

        // Assert

        $this->assertNull($result);
    }
}
