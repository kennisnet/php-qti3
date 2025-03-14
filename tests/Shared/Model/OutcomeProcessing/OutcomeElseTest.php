<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing\OutcomeElse;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OutcomeElseTest extends TestCase
{
    private OutcomeElse $outcomeElse;

    protected function setUp(): void
    {
        $this->outcomeElse = new OutcomeElse(
            new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))
        );
    }

    #[Test]
    public function testOutcomeElseChildren(): void
    {
        $children = $this->outcomeElse->children();
        $this->assertInstanceOf(SetOutcomeValue::class, $children[0]);
    }
}
