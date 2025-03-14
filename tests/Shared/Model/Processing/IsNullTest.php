<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IsNull;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IsNullTest extends TestCase
{
    private IsNull $isNull;

    protected function setUp(): void
    {
        $this->isNull = new IsNull(new Variable('variable'));
    }

    #[Test]
    public function testIsNull(): void
    {
        $this->assertInstanceOf(
            Variable::class,
            $this->isNull->children()[0]
        );
    }
}
