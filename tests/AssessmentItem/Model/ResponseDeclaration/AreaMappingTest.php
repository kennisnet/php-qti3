<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\AreaMapping;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AreaMappingTest extends TestCase
{
    private AreaMapping $areaMapping;

    protected function setUp(): void
    {
        $this->areaMapping = new AreaMapping(
            entries: [],
            defaultValue: 'default-value',
        );
    }

    #[Test]
    public function testAttributes(): void
    {
        $expectedAttributes = [
            'default-value' => 'default-value',
        ];

        $this->assertEquals($expectedAttributes, $this->areaMapping->attributes());
    }

    #[Test]
    public function testChildren(): void
    {
        $expectedChildren = [];

        $this->assertEquals($expectedChildren, $this->areaMapping->children());
    }
}
