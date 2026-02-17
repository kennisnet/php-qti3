<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentTest\Model;

use Qti3\AssessmentItem\Model\AssessmentItemId;
use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\AssessmentTest\Model\AssessmentTestId;
use Qti3\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use Qti3\AssessmentTest\Model\ItemRef\AssessmentItemRefCollection;
use Qti3\AssessmentTest\Model\Section\AssessmentSection;
use Qti3\AssessmentTest\Model\Section\AssessmentSectionCollection;
use Qti3\AssessmentTest\Model\TestPart\NavigationMode;
use Qti3\AssessmentTest\Model\TestPart\SubmissionMode;
use Qti3\AssessmentTest\Model\TestPart\TestPart;
use Qti3\AssessmentTest\Model\TestPart\TestPartCollection;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;

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
