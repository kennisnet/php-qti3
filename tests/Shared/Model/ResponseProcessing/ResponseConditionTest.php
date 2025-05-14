<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IsNull;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseCondition;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseElse;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseElseIf;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseIf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResponseConditionTest extends TestCase
{
    private ResponseCondition $responseCondition;

    protected function setUp(): void
    {
        $this->responseCondition = new ResponseCondition(
            if: new ResponseIf(
                new IsNull(new Variable('variable')),
                [new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))]
            ),
            elseIfs: [
                new ResponseElseIf(
                    new IsNull(new Variable('variable')),
                    [new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))]
                ),
            ],
            else: new ResponseElse(
                [new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))]
            )
        );
    }

    #[Test]
    public function testResponseCondition(): void
    {
        $this->assertInstanceOf(ResponseIf::class, $this->responseCondition->children()[0]);
        $this->assertInstanceOf(ResponseElseIf::class, $this->responseCondition->children()[1]);
        $this->assertInstanceOf(ResponseElse::class, $this->responseCondition->children()[2]);
    }
}
