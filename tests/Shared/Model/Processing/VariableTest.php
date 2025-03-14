<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class VariableTest extends TestCase
{
    private Variable $variable;

    protected function setUp(): void
    {
        $this->variable = new Variable('identifier');
    }

    #[Test]
    public function testVariable(): void
    {
        $this->assertEquals(['identifier' => 'identifier'], $this->variable->attributes());
    }
}
