<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\qtiAnd;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class qtiAndTest extends TestCase
{
    private qtiAnd $xAnd;

    protected function setUp(): void
    {
        $this->xAnd = new qtiAnd([]);
    }

    #[Test]
    public function testxAnd(): void
    {
        $this->assertEquals('qti-and', $this->xAnd->tagName());
        $this->assertEquals([], $this->xAnd->children());
    }
}
