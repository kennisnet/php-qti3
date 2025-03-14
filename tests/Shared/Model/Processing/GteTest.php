<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Gte;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GteTest extends TestCase
{
    private Gte $gte;

    protected function setUp(): void
    {
        $this->gte = new Gte(
            new Variable('variable1'),
            new Variable('variable2')
        );
    }

    #[Test]
    public function testGte(): void
    {
        $this->assertInstanceOf(Variable::class, $this->gte->children()[0]);
        $this->assertInstanceOf(Variable::class, $this->gte->children()[1]);
    }
}
