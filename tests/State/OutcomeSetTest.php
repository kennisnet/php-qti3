<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\State;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\Shared\Model\DefaultValue;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclaration;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\Value;
use App\SharedKernel\Domain\Qti\State\OutcomeSet;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OutcomeSetTest extends TestCase
{
    private OutcomeSet $outcomeSet;
    private OutcomeDeclarationCollection $outcomeDeclarations;

    protected function setUp(): void
    {
        $this->outcomeDeclarations = new OutcomeDeclarationCollection([]);
        $this->outcomeSet = new OutcomeSet($this->outcomeDeclarations);
    }

    #[Test]
    public function testGetOutcomeValueWithExistingOutcome(): void
    {
        // Arrange
        $this->outcomeSet->outcomes['test'] = 'value';
        $this->outcomeDeclarations->add(
            new OutcomeDeclaration(
                'test',
                BaseType::STRING,
                Cardinality::SINGLE
            )
        );

        // Act
        $result = $this->outcomeSet->getOutcomeValue('test');

        // Assert
        $this->assertEquals('value', $result);
    }

    #[Test]
    public function testGetOutcomeValueWithDefaultValue(): void
    {
        // Arrange
        $this->outcomeDeclarations->add(
            new OutcomeDeclaration(
                'test',
                BaseType::STRING,
                Cardinality::SINGLE,
                new DefaultValue(new Value('default'))
            )
        );
        $outcomeSet = new OutcomeSet($this->outcomeDeclarations);

        // Act
        $result = $outcomeSet->getOutcomeValue('test');

        // Assert
        $this->assertEquals('default', $result);
    }

    #[Test]
    public function testGetOutcomeValueWithNoDefaultValue(): void
    {
        // Arrange
        $this->outcomeDeclarations->add(
            new OutcomeDeclaration(
                'test',
                BaseType::STRING,
                Cardinality::SINGLE
            )
        );

        // Act
        $result = $this->outcomeSet->getOutcomeValue('test');

        // Assert
        $this->assertNull($result);
    }
}
