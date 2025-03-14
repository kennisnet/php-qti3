<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentTest\Model\Section;

use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section\Selection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SelectionTest extends TestCase
{
    private Selection $selection;

    protected function setUp(): void
    {
        $this->selection = new Selection(1, true);
    }

    #[Test]
    public function itShouldReturnAttributes(): void
    {
        $this->assertEquals([
            'select' => '1',
            'with-replacement' => 'true',
        ], $this->selection->attributes());
    }
}
