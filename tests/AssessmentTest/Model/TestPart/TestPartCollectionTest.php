<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart;

use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\TestPart;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\TestPartCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TestPartCollectionTest extends TestCase
{
    private TestPartCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new TestPartCollection();
    }

    #[Test]
    public function itShouldReturnTestPartClassAsType(): void
    {
        $this->assertEquals(TestPart::class, $this->collection->getType());
    }
}
