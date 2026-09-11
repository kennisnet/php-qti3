<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Shared\Model;

use Qti3\Shared\Model\ContentBody;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContentBodyTest extends TestCase
{
    private ContentBody $contentBody;
    private TextNode $textNode;

    protected function setUp(): void
    {
        $this->textNode = new TextNode('test');
        $this->contentBody = new ContentBody(new ContentNodeCollection([$this->textNode]));
    }

    #[Test]
    public function constructorInitializesValueCorrectly(): void
    {
        $this->assertInstanceOf(TextNode::class, $this->contentBody->children()[0]);
        $this->assertEquals('test', (string) $this->contentBody->children()[0]);
    }

    #[Test]
    public function acceptsAMathMlRootAsDirectChild(): void
    {
        $this->assertTrue(ContentBody::allowsAsDirectChild('math'));
    }

    #[Test]
    public function rejectsAMathMlElementThatIsNotTheRoot(): void
    {
        $this->assertFalse(ContentBody::allowsAsDirectChild('mi'));
    }

    #[Test]
    public function rejectsATagThatCannotStandOnItsOwn(): void
    {
        $this->assertFalse(ContentBody::allowsAsDirectChild('li'));
        $this->assertFalse(ContentBody::allowsAsDirectChild('td'));
    }

    #[Test]
    public function acceptsObjectWhichTheXsdListsAsFlowContent(): void
    {
        $this->assertTrue(ContentBody::allowsAsDirectChild('object'));
    }

    /** A package arrives unvalidated; reading one must not fail over a tag out of place. */
    #[Test]
    public function constructorAcceptsATagThatCannotStandOnItsOwn(): void
    {
        $contentBody = new ContentBody(new ContentNodeCollection([new HTMLTag('li')]));

        $this->assertSame('li', $contentBody->children()[0]->tagName());
    }
}
