<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\HottextInteraction;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\HottextInteraction\HottextInteraction;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HottextInteractionTest extends TestCase
{
    private HottextInteraction $hottextInteraction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hottextInteraction = new HottextInteraction(
            maxChoices: 1,
            content: new ContentNodeCollection([new TextNode('Textnode')]),
        );
    }

    #[Test]
    public function testAttributes(): void
    {
        $expectedAttributes = [
            'max-choices' => '1',
            'response-identifier' => 'RESPONSE',
        ];

        $this->assertSame($expectedAttributes, $this->hottextInteraction->attributes());
    }

    #[Test]
    public function testChildren(): void
    {
        $expectedChildren = [
            ...$this->hottextInteraction->content->all(),
        ];

        $this->assertSame($expectedChildren, $this->hottextInteraction->children());
    }
}
