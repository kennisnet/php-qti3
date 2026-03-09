<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Model\Interaction\HottextInteraction;

use Qti3\AssessmentItem\Model\Interaction\HottextInteraction\Hottext;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HottextTest extends TestCase
{
    private Hottext $hottext;
    private TextNode $textNode;

    protected function setUp(): void
    {
        $this->textNode = new TextNode('Example');
        $content = new ContentNodeCollection();
        $content->add($this->textNode);
        $this->hottext = new Hottext(
            content: $content,
            identifier: 'A',
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
        $this->assertSame([$this->textNode], $this->hottext->children());
    }
}
