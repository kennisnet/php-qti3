<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Lt;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LtTest extends TestCase
{
    private Lt $lt;

    protected function setUp(): void
    {
        $this->lt = new Lt(
            new Variable('variable1'),
            new Variable('variable2')
        );
    }

    #[Test]
    public function testLt(): void
    {
        $this->assertInstanceOf(Variable::class, $this->lt->children()[0]);
        $this->assertInstanceOf(Variable::class, $this->lt->children()[1]);
    }
}
