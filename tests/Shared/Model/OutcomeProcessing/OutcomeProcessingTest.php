<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing\OutcomeCondition;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing\OutcomeIf;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing\OutcomeProcessing;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IsNull;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OutcomeProcessingTest extends TestCase
{
    private OutcomeProcessing $outcomeProcessing;

    protected function setUp(): void
    {
        $this->outcomeProcessing = new OutcomeProcessing([
            new OutcomeCondition(
                if: new OutcomeIf(
                    new IsNull(new Variable('variable')),
                    new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))
                )
            ),
        ]);
    }

    #[Test]
    public function testOutcomeProcessingChildren(): void
    {
        $children = $this->outcomeProcessing->children();
        $this->assertInstanceOf(OutcomeCondition::class, $children[0]);
    }
}
