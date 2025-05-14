<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IsNull;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseIf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResponseIfTest extends TestCase
{
    private ResponseIf $responseIf;

    protected function setUp(): void
    {
        $this->responseIf = new ResponseIf(
            new IsNull(new Variable('variable')),
            [new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))]
        );
    }

    #[Test]
    public function testResponseIf(): void
    {
        $this->assertInstanceOf(IsNull::class, $this->responseIf->children()[0]);
        $this->assertInstanceOf(SetOutcomeValue::class, $this->responseIf->children()[1]);
    }
}
