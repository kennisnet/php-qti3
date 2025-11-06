<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IsNull;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseElseIf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResponseElseIfTest extends TestCase
{
    private ResponseElseIf $responseElseIf;

    protected function setUp(): void
    {
        $this->responseElseIf = new ResponseElseIf(
            new IsNull(new Variable('variable')),
            [new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))],
        );
    }

    #[Test]
    public function testResponseElseIf(): void
    {
        $this->assertInstanceOf(IsNull::class, $this->responseElseIf->children()[0]);
        $this->assertInstanceOf(SetOutcomeValue::class, $this->responseElseIf->children()[1]);
    }
}
