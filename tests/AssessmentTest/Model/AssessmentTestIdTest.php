<?php

declare(strict_types=1);

namespace Qti3\Tests\AssessmentTest\Model;

use Qti3\AssessmentTest\Model\AssessmentTestId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AssessmentTestIdTest extends TestCase
{
    #[Test]
    public function aValidAssessmentTestIdCanBeCreatedFromString(): void
    {
        $assessmentTestId =  AssessmentTestId::fromString('8394af58-8757-41b3-91e5-5534ec7a8175');

        $this->assertInstanceOf(AssessmentTestId::class, $assessmentTestId);
        $this->assertTrue(AssessmentTestId::isValid((string) $assessmentTestId));
    }

    #[Test]
    public function aValidAssessmentTestIdCanBeCreatedFromQuestionnaireId(): void
    {
        $assessmentTestId =  AssessmentTestId::fromQuestionnaireId(123);

        $this->assertEquals('5d5b5675-9994-5e0e-a795-52f3e27cb83f', (string) $assessmentTestId);
        $this->assertEquals(123, $assessmentTestId->questionnaireId());
    }

    #[Test]
    public function aAssessmentTestIdIsInvalidBasedOnAGivenValue(): void
    {
        $assessmentTestId = 'dummy';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided value `dummy` is invalid');

        AssessmentTestId::fromString($assessmentTestId);
    }
}
