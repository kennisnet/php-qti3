<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\AreaMapEntry;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\Rectangle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AreaMapEntryTest extends TestCase
{
    private AreaMapEntry $areaMapEntry;

    protected function setUp(): void
    {
        $this->areaMapEntry = new AreaMapEntry(
            shape: Rectangle::fromString('0,0,100,100'),
            mappedValue: 1,
        );
    }

    #[Test]
    public function testAttributes(): void
    {
        $expectedAttributes = [
            'shape' => 'rect',
            'coords' => '0,0,100,100',
            'mapped-value' => 1,
        ];

        $this->assertEquals($expectedAttributes, $this->areaMapEntry->attributes());
    }

    #[Test]
    public function testChildren(): void
    {
        $expectedChildren = [];

        $this->assertEquals($expectedChildren, $this->areaMapEntry->children());
    }
}
