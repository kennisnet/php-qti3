<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing\OutcomeElseIf;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IsNull;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OutcomeElseIfTest extends TestCase
{
    private OutcomeElseIf $outcomeElseIf;

    protected function setUp(): void
    {
        $this->outcomeElseIf = new OutcomeElseIf(
            new IsNull(new Variable('variable')),
            new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))
        );
    }

    #[Test]
    public function testOutcomeElseIfChildren(): void
    {
        $children = $this->outcomeElseIf->children();

        $this->assertInstanceOf(IsNull::class, $children[0]);
        $this->assertInstanceOf(SetOutcomeValue::class, $children[1]);
    }
}
