<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\State;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\CorrectResponse;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclaration;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclarationCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\Shared\Model\Value;
use App\SharedKernel\Domain\Qti\State\ResponseSet;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResponseSetTest extends TestCase
{
    private ResponseSet $responseSet;
    private ResponseDeclarationCollection $responseDeclarations;

    protected function setUp(): void
    {
        $this->responseDeclarations = new ResponseDeclarationCollection([]);
        $this->responseSet = new ResponseSet($this->responseDeclarations);
    }

    #[Test]
    public function testGetCorrectResponse(): void
    {
        // Arrange
        $correctResponse = new CorrectResponse([new Value('correct')]);
        $responseDeclaration = new ResponseDeclaration(
            BaseType::STRING,
            Cardinality::SINGLE,
            'test',
            $correctResponse
        );
        $this->responseDeclarations->add($responseDeclaration);

        // Act
        $result = $this->responseSet->getCorrectResponse('test');

        // Assert
        $this->assertEquals('correct', $result);
    }

    #[Test]
    public function testGetCorrectResponseWithNoCorrectResponse(): void
    {
        // Arrange
        $responseDeclaration = new ResponseDeclaration(
            BaseType::STRING,
            Cardinality::SINGLE,
            'test'
        );
        $this->responseDeclarations->add($responseDeclaration);

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Correct response for response declaration with identifier test not found');
        $this->responseSet->getCorrectResponse('test');
    }
}
