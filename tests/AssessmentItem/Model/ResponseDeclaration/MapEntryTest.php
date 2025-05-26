<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\MapEntry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MapEntryTest extends TestCase
{
    private MapEntry $mapEntry;

    protected function setUp(): void
    {
        $this->mapEntry = new MapEntry('key', 1);
    }

    #[Test]
    public function testAttributes(): void
    {
        $expectedAttributes = [
            'map-key' => 'key',
            'mapped-value' => 1,
            'case-sensitive' => null,
        ];

        $this->assertEquals($expectedAttributes, $this->mapEntry->attributes());
    }

    #[Test]
    public function evaluateSingularValue(): void
    {
        $this->assertEquals(1, $this->mapEntry->evaluate(
            'key'
        ));
    }
}
