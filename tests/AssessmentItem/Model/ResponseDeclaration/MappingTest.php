<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\MapEntry;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\Mapping;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MappingTest extends TestCase
{
    private MapEntry $mapEntry;
    private Mapping $mapping;

    protected function setUp(): void
    {
        $this->mapEntry = new MapEntry('key', 'value');
        $this->mapping = new Mapping([$this->mapEntry], 'default');
    }

    #[Test]
    public function testAttributes(): void
    {
        $expectedAttributes = [
            'default-value' => 'default',
        ];

        $this->assertEquals($expectedAttributes, $this->mapping->attributes());
    }

    #[Test]
    public function testChildren(): void
    {
        $expectedChildren = [$this->mapEntry];

        $this->assertEquals($expectedChildren, $this->mapping->children());
    }
}
