<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Model\RubricBlock;

use Qti3\AssessmentItem\Model\RubricBlock\RubricBlock;
use Qti3\AssessmentItem\Model\RubricBlock\RubricBlockCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RubricBlockCollectionTest extends TestCase
{
    private RubricBlockCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new RubricBlockCollection();
    }

    #[Test]
    public function itShouldReturnRubricBlockClassAsType(): void
    {
        $this->assertEquals(RubricBlock::class, $this->collection->getType());
    }

    #[Test]
    public function itShouldBeEmptyByDefault(): void
    {
        $this->assertTrue($this->collection->isEmpty());
    }
}
