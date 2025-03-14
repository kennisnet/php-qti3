<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentTest\Model\Section;

use App\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef\AssessmentItemRefCollection;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section\AssessmentSection;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section\Ordering;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section\Selection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AssessmentSectionTest extends TestCase
{
    private AssessmentSection $assessmentSection;

    protected function setUp(): void
    {
        parent::setUp();

        $itemRefs = new AssessmentItemRefCollection(
            [new AssessmentItemRef(
                identifier: 'ITEM001',
                href: 'ITEM001.xml',
            )]
        );
        $selection = new Selection(1, false);
        $ordering = new Ordering(true);

        $this->assessmentSection = new AssessmentSection(
            identifier: 'id',
            title: 'title',
            assessmentItemRefs: $itemRefs,
            selection: $selection,
            ordering: $ordering
        );
    }

    #[Test]
    public function testAttributesAndChildren(): void
    {
        $this->assertSame(
            [
                'identifier' => 'id',
                'title' => 'title',
                'visible' => 'true',
            ],
            $this->assessmentSection->attributes()
        );

        $this->assertSame(
            [
                $this->assessmentSection->selection,
                $this->assessmentSection->ordering,
                $this->assessmentSection->assessmentItemRefs[0],
            ],
            $this->assessmentSection->children()
        );
    }
}
