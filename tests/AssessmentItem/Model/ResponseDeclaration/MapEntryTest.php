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
        $this->mapEntry = new MapEntry('key', 'value');
    }

    #[Test]
    public function testAttributes(): void
    {
        $expectedAttributes = [
            'map-key' => 'key',
            'mapped-value' => 'value',
            'case-sensitive' => null,
        ];

        $this->assertEquals($expectedAttributes, $this->mapEntry->attributes());
    }
}
