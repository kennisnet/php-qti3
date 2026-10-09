<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Model\ResponseDeclaration;

use Qti3\AssessmentItem\Model\ResponseDeclaration\AreaMapping;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AreaMappingTest extends TestCase
{
    private AreaMapping $areaMapping;

    protected function setUp(): void
    {
        $this->areaMapping = new AreaMapping(
            entries: [],
            defaultValue: 0,
            lowerBound: 1,
            upperBound: 2,
        );
    }

    #[Test]
    public function testAttributes(): void
    {
        $expectedAttributes = [
            'default-value' => '0',
            'lower-bound' => '1',
            'upper-bound' => '2',
        ];

        $this->assertSame($expectedAttributes, $this->areaMapping->attributes());
    }

    #[Test]
    public function testAttributesOmitUnsetValues(): void
    {
        $this->assertSame([], (new AreaMapping([]))->attributes());
    }

    #[Test]
    public function testChildren(): void
    {
        $expectedChildren = [];

        $this->assertEquals($expectedChildren, $this->areaMapping->children());
    }
}
