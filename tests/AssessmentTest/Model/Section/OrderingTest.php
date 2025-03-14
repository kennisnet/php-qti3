<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentTest\Model\Section;

use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section\Ordering;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OrderingTest extends TestCase
{
    private Ordering $ordering;

    protected function setUp(): void
    {
        $this->ordering = new Ordering(true);
    }

    #[Test]
    public function testAttributes(): void
    {
        $this->assertEquals([
            'shuffle' => 'true',
        ], $this->ordering->attributes());
    }
}
