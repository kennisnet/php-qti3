<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Gt;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GtTest extends TestCase
{
    private Gt $gt;

    protected function setUp(): void
    {
        $this->gt = new Gt(
            new Variable('variable1'),
            new Variable('variable2')
        );
    }

    #[Test]
    public function testGt(): void
    {
        $this->assertInstanceOf(Variable::class, $this->gt->children()[0]);
        $this->assertInstanceOf(Variable::class, $this->gt->children()[1]);
    }
}
