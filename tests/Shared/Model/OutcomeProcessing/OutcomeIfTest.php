<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing\OutcomeIf;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IsNull;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OutcomeIfTest extends TestCase
{
    private OutcomeIf $outcomeIf;

    protected function setUp(): void
    {
        $this->outcomeIf = new OutcomeIf(
            new IsNull(new Variable('variable')),
            new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value')),
        );
    }

    #[Test]
    public function testOutcomeIfChildren(): void
    {
        $children = $this->outcomeIf->children();
        $this->assertInstanceOf(IsNull::class, $children[0]);
        $this->assertInstanceOf(SetOutcomeValue::class, $children[1]);
    }
}
