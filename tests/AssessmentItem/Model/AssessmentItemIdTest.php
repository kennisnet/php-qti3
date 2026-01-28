<?php

declare(strict_types=1);

namespace Qti3\Tests\AssessmentItem\Model;

use Qti3\AssessmentItem\Model\AssessmentItemId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AssessmentItemIdTest extends TestCase
{
    private string $validUuid;
    private int $validQuestionnaireId;
    private int $validQuestionnaireItemIndex;

    protected function setUp(): void
    {
        $this->validUuid = '8394af58-8757-41b3-91e5-5534ec7a8175';
        $this->validQuestionnaireId = 1;
        $this->validQuestionnaireItemIndex = 0;
    }

    #[Test]
    public function aValidAssessmentItemIdCanBeCreatedFromString(): void
    {
        $assessmentItemId = AssessmentItemId::fromString($this->validUuid);

        $this->assertInstanceOf(AssessmentItemId::class, $assessmentItemId);
        $this->assertTrue(AssessmentItemId::isValid((string) $assessmentItemId));
    }

    #[Test]
    public function aAssessmentItemIdCanBeCreatedFromQuestionnaire(): void
    {
        $assessmentItemId = AssessmentItemId::fromQuestionnaire($this->validQuestionnaireId, $this->validQuestionnaireItemIndex);

        $this->assertInstanceOf(AssessmentItemId::class, $assessmentItemId);
        $this->assertTrue(AssessmentItemId::isValid((string) $assessmentItemId));

        $this->assertEquals($this->validQuestionnaireId, $assessmentItemId->questionnaireId());
        $this->assertEquals($this->validQuestionnaireItemIndex, $assessmentItemId->questionnaireItemIndex());
    }

    #[Test]
    public function aAssessmentItemIdIsInvalidBasedOnAGivenValue(): void
    {
        $invalidValue = 'dummy';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided value `dummy` is invalid');

        AssessmentItemId::fromString($invalidValue);
    }
}
