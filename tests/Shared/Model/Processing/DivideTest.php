<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Divide;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DivideTest extends TestCase
{
    private Divide $divide;

    protected function setUp(): void
    {
        $this->divide = new Divide(
            new Variable('variable1'),
            new Variable('variable2')
        );
    }

    #[Test]
    public function testDivide(): void
    {
        $this->assertInstanceOf(Variable::class, $this->divide->children()[0]);
        $this->assertInstanceOf(Variable::class, $this->divide->children()[1]);
    }
}
