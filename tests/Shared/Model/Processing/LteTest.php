<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Lte;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LteTest extends TestCase
{
    private Lte $lte;

    protected function setUp(): void
    {
        $this->lte = new Lte(
            new Variable('variable1'),
            new Variable('variable2')
        );
    }

    #[Test]
    public function testLte(): void
    {
        $this->assertInstanceOf(Variable::class, $this->lte->children()[0]);
        $this->assertInstanceOf(Variable::class, $this->lte->children()[1]);
    }
}
