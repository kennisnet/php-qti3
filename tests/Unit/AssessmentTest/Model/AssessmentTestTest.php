<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentTest\Model;

use Qti3\AssessmentItem\Model\AssessmentItemId;
use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\AssessmentTest\Model\AssessmentTestId;
use Qti3\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use Qti3\AssessmentTest\Model\TestPart\TestPartCollection;
use Qti3\Package\Exception\InvalidItemOrderException;
use Qti3\Package\Exception\InvalidQtiPackageException;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
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
        $assessmentItemId = AssessmentItemId::fromString('10fe19b2-8b6e-53fa-8522-1220c67ddce1');
        $itemRef = $assessmentTest->findItemRef($assessmentItemId);
        $this->assertSame((string) $assessmentItemId, (string) $itemRef->identifier);
    }

    #[Test]
    public function anItemRefCannotBeFound(): void
    {
        $this->expectException(RuntimeException::class);
        $assessmentTest = AssessmentTestStub::assessmentTest();
        $assessmentItemId = AssessmentItemId::fromString('22222222-2222-2222-2222-222222222222');
        $assessmentTest->findItemRef($assessmentItemId);
    }

    #[Test]
    public function anItemRefCanBeAdded(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTest();

        $assessmentTest->addItemRef(new AssessmentItemRef(AssessmentItemId::fromString('ITEM002'), 'ITEM002.xml'));

        $this->assertSame(
            ['10fe19b2-8b6e-53fa-8522-1220c67ddce1', 'ITEM002'],
            $assessmentTest->getItemIdentifiers(),
        );
    }

    #[Test]
    public function addingADuplicateItemRefIsRejected(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTest();

        $this->expectException(InvalidQtiPackageException::class);

        $assessmentTest->addItemRef(new AssessmentItemRef(
            AssessmentItemId::fromString('10fe19b2-8b6e-53fa-8522-1220c67ddce1'),
            'ITEM001.xml',
        ));
    }

    #[Test]
    public function addingAnItemRefToATestWithoutSectionsIsRejected(): void
    {
        $assessmentTest = new AssessmentTest(
            AssessmentTestId::fromString('e076edda-bf70-5105-a9a9-118d7eecd0c4'),
            new OutcomeDeclarationCollection(),
            new TestPartCollection(),
        );

        $this->expectException(InvalidQtiPackageException::class);

        $assessmentTest->addItemRef(new AssessmentItemRef(AssessmentItemId::fromString('ITEM001'), 'ITEM001.xml'));
    }

    #[Test]
    public function itemRefsCanBeReordered(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTestWithTwoItems();

        $assessmentTest->reorderItemRefs([
            '22222222-2222-2222-2222-222222222222',
            '10fe19b2-8b6e-53fa-8522-1220c67ddce1',
        ]);

        $this->assertSame(
            ['22222222-2222-2222-2222-222222222222', '10fe19b2-8b6e-53fa-8522-1220c67ddce1'],
            $assessmentTest->getItemIdentifiers(),
        );
    }

    #[Test]
    public function reorderingWithAnUnknownIdentifierIsRejected(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTestWithTwoItems();

        $this->expectException(InvalidItemOrderException::class);

        $assessmentTest->reorderItemRefs([
            '10fe19b2-8b6e-53fa-8522-1220c67ddce1',
            'UNKNOWN',
        ]);
    }

    #[Test]
    public function reorderingWithAMissingIdentifierIsRejected(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTestWithTwoItems();

        $this->expectException(InvalidItemOrderException::class);

        $assessmentTest->reorderItemRefs(['10fe19b2-8b6e-53fa-8522-1220c67ddce1']);
    }

    #[Test]
    public function reorderingWithADuplicateIdentifierIsRejected(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTestWithTwoItems();

        $this->expectException(InvalidItemOrderException::class);

        $assessmentTest->reorderItemRefs([
            '10fe19b2-8b6e-53fa-8522-1220c67ddce1',
            '10fe19b2-8b6e-53fa-8522-1220c67ddce1',
        ]);
    }
}
