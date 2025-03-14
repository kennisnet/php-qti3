<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SetOutcomeValueTest extends TestCase
{
    private SetOutcomeValue $setOutcomeValue;

    protected function setUp(): void
    {
        $this->setOutcomeValue = new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'));
    }

    #[Test]
    public function testSetOutcomeValue(): void
    {
        $this->assertEquals(['identifier' => 'identifier'], $this->setOutcomeValue->attributes());
        $this->assertInstanceOf(BaseValue::class, $this->setOutcomeValue->children()[0]);
    }
}
