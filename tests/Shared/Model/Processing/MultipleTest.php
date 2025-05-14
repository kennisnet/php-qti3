<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Multiple;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MultipleTest extends TestCase
{
    private Multiple $multiple;

    protected function setUp(): void
    {
        $this->multiple = new Multiple([]);
    }

    #[Test]
    public function testChildren(): void
    {
        $this->assertEquals([], $this->multiple->children());
    }
}
