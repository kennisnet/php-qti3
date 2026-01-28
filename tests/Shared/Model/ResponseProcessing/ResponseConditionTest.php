<?php

declare(strict_types=1);

namespace Qti3\Tests\Shared\Model\ResponseProcessing;

use Qti3\Shared\Model\BaseType;
use Qti3\Shared\Model\Processing\BaseValue;
use Qti3\Shared\Model\Processing\IsNull;
use Qti3\Shared\Model\Processing\SetOutcomeValue;
use Qti3\Shared\Model\Processing\Variable;
use Qti3\Shared\Model\ResponseProcessing\ResponseCondition;
use Qti3\Shared\Model\ResponseProcessing\ResponseElse;
use Qti3\Shared\Model\ResponseProcessing\ResponseElseIf;
use Qti3\Shared\Model\ResponseProcessing\ResponseIf;
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
                [new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))],
            ),
            elseIfs: [
                new ResponseElseIf(
                    new IsNull(new Variable('variable')),
                    [new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))],
                ),
            ],
            else: new ResponseElse(
                [new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))],
            ),
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
