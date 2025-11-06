<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentTest\Model;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItemId;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTest;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTestId;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef\AssessmentItemRefCollection;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section\AssessmentSection;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section\AssessmentSectionCollection;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\NavigationMode;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\SubmissionMode;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\TestPart;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\TestPartCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;

class AssessmentTestStub
{
    public static function assessmentTest(): AssessmentTest
    {
        return new AssessmentTest(
            AssessmentTestId::fromQuestionnaireId(1),
            new OutcomeDeclarationCollection([]),
            new TestPartCollection([
                new TestPart(
                    'id',
                    NavigationMode::LINEAR,
                    SubmissionMode::INDIVIDUAL,
                    new AssessmentSectionCollection([
                        new AssessmentSection(
                            'id',
                            'title',
                            new AssessmentItemRefCollection([
                                new AssessmentItemRef(
                                    'ITEM001',
                                    'ITEM001.xml',
                                    AssessmentItemId::fromQuestionnaire(1, 0),
                                ),
                            ]),
                        ),
                    ]),
                ),
            ]),
            'title',
        );
    }

    public static function assessmentTestWithTwoItems(): AssessmentTest
    {
        return new AssessmentTest(
            AssessmentTestId::fromQuestionnaireId(1),
            new OutcomeDeclarationCollection([]),
            new TestPartCollection([
                new TestPart(
                    'id',
                    NavigationMode::LINEAR,
                    SubmissionMode::INDIVIDUAL,
                    new AssessmentSectionCollection([
                        new AssessmentSection(
                            'id',
                            'title',
                            new AssessmentItemRefCollection([
                                new AssessmentItemRef(
                                    'ITEM001',
                                    'ITEM001.xml',
                                    AssessmentItemId::fromQuestionnaire(1, 0),
                                ),
                                new AssessmentItemRef(
                                    'ITEM002',
                                    'ITEM002.xml',
                                    AssessmentItemId::fromQuestionnaire(1, 1),
                                ),
                            ]),
                        ),
                    ]),
                ),
            ]),
            'title',
        );
    }
}
