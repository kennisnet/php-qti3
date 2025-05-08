<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Equal;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EqualTest extends TestCase
{
    private Equal $equal;

    protected function setUp(): void
    {
        $this->equal = new Equal(
            new Variable('variable1'),
            new Variable('variable2')
        );
    }

    #[Test]
    public function testGt(): void
    {
        $this->assertInstanceOf(Variable::class, $this->equal->children()[0]);
        $this->assertInstanceOf(Variable::class, $this->equal->children()[1]);
    }
}
