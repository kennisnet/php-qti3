<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IsNull;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseCondition;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseIf;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseProcessing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResponseProcessingTest extends TestCase
{
    private ResponseProcessing $responseProcessing;

    protected function setUp(): void
    {
        $this->responseProcessing = new ResponseProcessing([
            new ResponseCondition(
                if: new ResponseIf(
                    new IsNull(new Variable('variable')),
                    [new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))]
                )
            ),
        ]);
    }

    #[Test]
    public function testResponseProcessing(): void
    {
        $this->assertInstanceOf(ResponseCondition::class, $this->responseProcessing->children()[0]);
    }

    #[Test]
    public function testMatchCorrect(): void
    {
        $responseProcessing = ResponseProcessing::matchCorrect();
        $this->assertInstanceOf(ResponseCondition::class, $responseProcessing->children()[0]);
    }

    #[Test]
    public function testMapResponse(): void
    {
        $responseProcessing = ResponseProcessing::mapResponse();
        $this->assertInstanceOf(ResponseCondition::class, $responseProcessing->children()[0]);
    }
}
