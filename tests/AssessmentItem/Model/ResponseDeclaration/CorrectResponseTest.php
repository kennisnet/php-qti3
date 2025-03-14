<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\CorrectResponse;
use App\SharedKernel\Domain\Qti\Shared\Model\Value;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CorrectResponseTest extends TestCase
{
    private CorrectResponse $correctResponse;

    protected function setUp(): void
    {
        $this->correctResponse = new CorrectResponse([
            new Value('value'),
        ]);
    }

    #[Test]
    public function testAttributes(): void
    {
        $expectedAttributes = [];

        $this->assertEquals($expectedAttributes, $this->correctResponse->attributes());
    }

    #[Test]
    public function testChildren(): void
    {
        $this->assertCount(1, $this->correctResponse->children());
    }

    #[Test]
    public function testConstructWithEmptyValues(): void
    {
        $this->expectExceptionMessage('CorrectResponse must have at least one value');

        new CorrectResponse([]);
    }
}
