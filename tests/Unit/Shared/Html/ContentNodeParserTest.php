<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Shared\Html;

use DOMDocument;
use DOMNode;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Shared\Html\ContentNodeParser;
use Qti3\Shared\Model\Comment;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\TextNode;

class ContentNodeParserTest extends TestCase
{
    private ContentNodeParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ContentNodeParser();
    }

    private function firstChild(string $xml): DOMNode
    {
        return $this->nthChild($xml, 0);
    }

    private function nthChild(string $xml, int $index): DOMNode
    {
        $document = new DOMDocument();
        $document->loadXML($xml);

        return $document->documentElement->childNodes->item($index);
    }

    #[Test]
    public function parsesATextNode(): void
    {
        $node = $this->firstChild('<root>Hello world</root>');

        $result = $this->parser->parse($node);

        $this->assertInstanceOf(TextNode::class, $result);
        $this->assertSame('Hello world', $result->content);
    }

    #[Test]
    public function parsesWhitespaceOnlyTextAsNull(): void
    {
        $node = $this->firstChild("<root>   \n\t  </root>");

        $result = $this->parser->parse($node);

        $this->assertNull($result);
    }

    #[Test]
    public function keepsTheWhitespaceThatSeparatesTwoInlineElements(): void
    {
        $node = $this->nthChild('<root><p><strong>een</strong> <em>twee</em></p></root>', 0);

        $result = $this->parser->parse($node);

        $this->assertInstanceOf(HTMLTag::class, $result);
        $children = $result->children();
        $this->assertCount(3, $children);
        $this->assertInstanceOf(TextNode::class, $children[1]);
        $this->assertSame(' ', $children[1]->content);
    }

    #[Test]
    public function keepsTheWhitespaceAroundAnInlineInteraction(): void
    {
        $node = $this->firstChild('<root><qti-text-entry-interaction/> <qti-text-entry-interaction/></root>')->nextSibling;

        $result = $this->parser->parse($node);

        $this->assertInstanceOf(TextNode::class, $result);
    }

    #[Test]
    public function dropsTheWhitespaceBetweenTwoBlockElements(): void
    {
        $node = $this->firstChild("<root><p>een</p>\n  <p>twee</p></root>")->nextSibling;

        $this->assertNull($this->parser->parse($node));
    }

    #[Test]
    public function dropsTheWhitespaceAtTheEdgesOfABlockElement(): void
    {
        $node = $this->nthChild("<root><p>\n  <strong>een</strong>\n</p></root>", 0);

        $result = $this->parser->parse($node);

        $this->assertInstanceOf(HTMLTag::class, $result);
        $this->assertCount(1, $result->children());
    }

    #[Test]
    public function dropsTheWhitespaceBetweenABlockElementAndAnInlineOne(): void
    {
        $node = $this->firstChild("<root><div>een</div>\n<qti-text-entry-interaction/></root>")->nextSibling;

        $this->assertNull($this->parser->parse($node));
    }

    #[Test]
    public function parsesAnElementWithAttributesAndNestedChildren(): void
    {
        $node = $this->firstChild('<root><p class="intro" id="p1">Nested <em>emphasis</em> text</p></root>');

        $result = $this->parser->parse($node);

        $this->assertInstanceOf(HTMLTag::class, $result);
        $this->assertSame('p', $result->tagName());
        $this->assertSame(['class' => 'intro', 'id' => 'p1'], $result->attributes());

        $children = $result->children();
        $this->assertCount(3, $children);
        $this->assertInstanceOf(TextNode::class, $children[0]);
        $this->assertSame('Nested ', $children[0]->content);
        $this->assertInstanceOf(HTMLTag::class, $children[1]);
        $this->assertSame('em', $children[1]->tagName());
        $this->assertInstanceOf(TextNode::class, $children[1]->children()[0]);
        $this->assertSame('emphasis', $children[1]->children()[0]->content);
        $this->assertInstanceOf(TextNode::class, $children[2]);
        $this->assertSame(' text', $children[2]->content);
    }

    #[Test]
    public function keepsACommentAsAComment(): void
    {
        $node = $this->firstChild('<root><!-- a comment --></root>');

        $result = $this->parser->parse($node);

        $this->assertInstanceOf(Comment::class, $result);
        $this->assertSame(' a comment ', $result->content);
    }

    #[Test]
    public function parsingADisallowedTagThrows(): void
    {
        $node = $this->firstChild('<root><script>alert(1)</script></root>');

        $this->expectException(InvalidArgumentException::class);

        $this->parser->parse($node);
    }
}
