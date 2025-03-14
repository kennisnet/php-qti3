<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model;

use App\SharedKernel\Domain\Qti\Shared\Model\DefaultValue;
use App\SharedKernel\Domain\Qti\Shared\Model\IContentNode;
use App\SharedKernel\Domain\Qti\Shared\Model\Value;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefaultValueTest extends TestCase
{
    private DefaultValue $defaultValue;
    private Value $value;

    protected function setUp(): void
    {
        $this->value = new Value('test');
        $this->defaultValue = new DefaultValue($this->value);
    }

    #[Test]
    public function constructorInitializesValueCorrectly(): void
    {
        $this->assertSame($this->value, $this->defaultValue->value);
    }

    #[Test]
    public function childrenReturnsArrayWithValue(): void
    {
        $children = $this->defaultValue->children();

        $this->assertIsArray($children);
        $this->assertCount(1, $children);
        $this->assertInstanceOf(IContentNode::class, $children[0]);
        $this->assertSame($this->value, $children[0]);
    }
}
