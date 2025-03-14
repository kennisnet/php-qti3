<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentTest\Model;

use App\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTest;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTestCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AssessmentTestCollectionTest extends TestCase
{
    private AssessmentTestCollection $collection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collection = new AssessmentTestCollection();
    }

    #[Test]
    public function itShouldReturnAssessmentTestClassAsType(): void
    {
        $this->assertEquals(AssessmentTest::class, $this->collection->getType());
    }
}
