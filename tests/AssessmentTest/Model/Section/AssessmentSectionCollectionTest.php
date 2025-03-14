<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentTest\Model\Section;

use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section\AssessmentSection;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section\AssessmentSectionCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AssessmentSectionCollectionTest extends TestCase
{
    private AssessmentSectionCollection $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new AssessmentSectionCollection();
    }

    #[Test]
    public function itShouldReturnSectionClassAsType(): void
    {
        $this->assertEquals(AssessmentSection::class, $this->collection->getType());
    }
}
