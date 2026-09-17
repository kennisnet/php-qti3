<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Shared\Model;

use Qti3\AssessmentItem\Model\Interaction\ChoiceInteraction\ChoiceInteraction;
use Qti3\Shared\Html\HtmlFragmentParser;
use Qti3\Shared\Model\ContentBody;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\DataProvider;
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
    public function constructorAcceptsATagThatCannotStandOnItsOwn(): void
    {
        $contentBody = new ContentBody(new ContentNodeCollection([new HTMLTag('li')]));

        $this->assertSame('li', $contentBody->children()[0]->tagName());
    }

    #[Test]
    #[DataProvider('hasContentProvider')]
    public function hasContentReflectsWhetherTheFragmentIsVisuallyEmpty(string $html, bool $expected): void
    {
        $contentBody = (new HtmlFragmentParser())->parse($html);

        $this->assertSame($expected, $contentBody->hasContent());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function hasContentProvider(): iterable
    {
        yield 'nbsp only' => ['<p>&nbsp;</p>', false];
        yield 'a lone break' => ['<p><br></p>', false];
        yield 'an empty paragraph' => ['<p></p>', false];
        yield 'a wrapped lone break' => ['<div><p><br></p></div>', false];
        yield 'a zero-width space' => ['<p>&#8203;</p>', false];
        yield 'an empty inline tag' => ['<p><strong></strong></p>', false];

        yield 'text' => ['<p>a</p>', true];
        yield 'an image' => ['<p><img src="a.png" alt=""/></p>', true];
        yield 'a horizontal rule' => ['<hr/>', true];
        yield 'a table' => ['<table><tr><td>1</td></tr></table>', true];
        yield 'MathML with an actual formula' => ['<math><mrow><mn>1</mn><mo>+</mo><mn>1</mn></mrow></math>', true];
        yield 'an empty table' => ['<table></table>', true];
        yield 'a video' => ['<video src="a.mp4"></video>', true];
        yield 'an audio element' => ['<audio src="a.mp3"></audio>', true];
        yield 'an empty formula' => ['<math></math>', false];
        yield 'a comment only' => ['<p><!-- leeg --></p>', false];
    }

    #[Test]
    public function hasContentIsTrueForANodeTypeOtherThanTextOrHtml(): void
    {
        $contentBody = new ContentBody(new ContentNodeCollection([new ChoiceInteraction([])]));

        $this->assertTrue($contentBody->hasContent());
    }

    #[Test]
    public function hasContentIsFalseForAnEmptyContentBody(): void
    {
        $contentBody = new ContentBody(new ContentNodeCollection());

        $this->assertFalse($contentBody->hasContent());
    }
}
