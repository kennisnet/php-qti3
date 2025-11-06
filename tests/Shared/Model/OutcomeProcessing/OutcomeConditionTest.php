<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing\OutcomeCondition;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing\OutcomeElse;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing\OutcomeElseIf;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing\OutcomeIf;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IsNull;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OutcomeConditionTest extends TestCase
{
    private OutcomeCondition $outcomeCondition;

    protected function setUp(): void
    {
        $this->outcomeCondition = new OutcomeCondition(
            if: new OutcomeIf(
                new IsNull(new Variable('variable')),
                new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value')),
            ),
            elseIfs: [
                new OutcomeElseIf(
                    new IsNull(new Variable('variable')),
                    new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value')),
                ),
            ],
            else: new OutcomeElse(
                new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value')),
            ),
        );
    }

    #[Test]
    public function testOutcomeConditionChildren(): void
    {
        $children = $this->outcomeCondition->children();

        $this->assertInstanceOf(OutcomeIf::class, $children[0]);
        $this->assertInstanceOf(OutcomeElseIf::class, $children[1]);
        $this->assertInstanceOf(OutcomeElse::class, $children[2]);
    }
}
