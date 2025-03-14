<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\HottextInteraction;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\HottextInteraction\Hottext;
use App\SharedKernel\Domain\Qti\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HottextTest extends TestCase
{
    private Hottext $hottext;

    protected function setUp(): void
    {
        $this->hottext = new Hottext(
            content: new TextNode('Example'),
            identifier: 'A'
        );
    }

    #[Test]
    public function testAttributes(): void
    {
        $expectedAttributes = [
            'identifier' => 'A',
        ];

        $this->assertSame($expectedAttributes, $this->hottext->attributes());
    }

    #[Test]
    public function testChildren(): void
    {
        $expectedChildren = [
            $this->hottext->content,
        ];

        $this->assertSame($expectedChildren, $this->hottext->children());
    }
}
