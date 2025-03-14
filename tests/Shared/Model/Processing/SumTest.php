<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Sum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SumTest extends TestCase
{
    private Sum $sum;

    protected function setUp(): void
    {
        $this->sum = new Sum([]);
    }

    #[Test]
    public function testSum(): void
    {
        $this->assertEquals([], $this->sum->children());
    }
}
