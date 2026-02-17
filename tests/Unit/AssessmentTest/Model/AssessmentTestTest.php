<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentTest\Model;

use Qti3\AssessmentItem\Model\AssessmentItemId;
use Qti3\Tests\Unit\AssessmentItem\Model\AssessmentItemStub;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AssessmentTestTest extends TestCase
{
    #[Test]
    public function aValidIdCanBeGiven(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTest();
        $this->assertSame('e076edda-bf70-5105-a9a9-118d7eecd0c4', (string) $assessmentTest->identifier);
    }

    #[Test]
    public function aTitleCanBeGiven(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTest();
        $this->assertSame('title', $assessmentTest->title);
    }

    #[Test]
    public function outcomeDeclarationsCanBeRetrieved(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTest();
        $this->assertCount(0, $assessmentTest->outcomeDeclarations);
    }

    #[Test]
    public function itemRefsCanBeRetrieved(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTest();
        $this->assertCount(1, $assessmentTest->getItemRefs());
    }

    #[Test]
    public function itemsCanBeValidated(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTest();
        $items = [AssessmentItemStub::assessmentItem()];
        $assessmentTest->validateItems($items);
        $this->assertTrue(true);
    }

    #[Test]
    public function itemsCanBeValidatedWithInvalidItems(): void
    {
        $this->expectException(RuntimeException::class);
        $assessmentTest = AssessmentTestStub::assessmentTest();
        $assessmentTest->validateItems([]);
    }

    #[Test]
    public function anItemRefCanBeFound(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTest();
        $assessmentItemId = AssessmentItemId::fromQuestionnaire(1, 0);
        $itemRef = $assessmentTest->findItemRef($assessmentItemId);
        $this->assertSame((string) $assessmentItemId, (string) $itemRef->itemId);
    }

    #[Test]
    public function anItemRefCannotBeFound(): void
    {
        $this->expectException(RuntimeException::class);
        $assessmentTest = AssessmentTestStub::assessmentTest();
        $assessmentItemId = AssessmentItemId::fromQuestionnaire(1, 1);
        $assessmentTest->findItemRef($assessmentItemId);
    }
}
