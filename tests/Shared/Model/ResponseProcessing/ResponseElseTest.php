<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseElse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResponseElseTest extends TestCase
{
    private ResponseElse $responseElse;

    protected function setUp(): void
    {
        $this->responseElse = new ResponseElse(
            new SetOutcomeValue('identifier', new BaseValue(BaseType::STRING, 'value'))
        );
    }

    #[Test]
    public function testResponseElse(): void
    {
        $this->assertInstanceOf(SetOutcomeValue::class, $this->responseElse->children()[0]);
    }
}
