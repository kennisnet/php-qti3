<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Shared\Html;

use DOMDocument;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Html\ContentNodeParser;
use Qti3\Shared\Html\HtmlFragmentParser;
use Qti3\Shared\Model\Comment;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\TextNode;

class HtmlFragmentParserTest extends TestCase
{
    private HtmlFragmentParser $parser;

    protected function setUp(): void
    {
        $this->parser = new HtmlFragmentParser(new ContentNodeParser());
    }

    #[Test]
    public function parseTurnsAFragmentIntoAContentBody(): void
    {
        $contentBody = $this->parser->parse('<p>Lees <strong>dit</strong></p><ul><li>Een</li></ul>');

        $content = $contentBody->content->all();
        $this->assertCount(2, $content);
        $this->assertInstanceOf(HTMLTag::class, $content[0]);
        $this->assertSame('p', $content[0]->tagName());
        $this->assertSame('ul', $content[1]->tagName());
    }

    #[Test]
    public function parsePreservesUtf8Text(): void
    {
        $contentBody = $this->parser->parse('<p>Curaçao — ok</p>');

        $this->assertSame('Curaçao — ok', $contentBody->content->all()[0]->children()[0]->content);
    }

    #[Test]
    public function parseDecodesNbspToANonBreakingSpaceCharacter(): void
    {
        $contentBody = $this->parser->parse('<p>a&nbsp;b</p>');

        $this->assertSame("a\u{00a0}b", $contentBody->content->all()[0]->children()[0]->content);
    }

    #[Test]
    public function parseKeepsBareTextAsASingleTextNodeWithoutAnImpliedWrapper(): void
    {
        $contentBody = $this->parser->parse('hallo');

        $content = $contentBody->content->all();
        $this->assertCount(1, $content);
        $this->assertInstanceOf(TextNode::class, $content[0]);
        $this->assertSame('hallo', $content[0]->content);
    }

    #[Test]
    public function parseKeepsAComment(): void
    {
        $contentBody = $this->parser->parse('<p>a</p><!-- notitie -->');

        $this->assertInstanceOf(Comment::class, $contentBody->content->all()[1]);
    }

    #[Test]
    public function parseDropsWhitespaceBetweenBlockElements(): void
    {
        $contentBody = $this->parser->parse("<p>a</p>\n  <p>b</p>");

        $this->assertCount(2, $contentBody->content->all());
    }

    #[Test]
    public function parseKeepsWhitespaceBetweenInlineElements(): void
    {
        $contentBody = $this->parser->parse('<p><strong>vet</strong> <em>cursief</em></p>');

        $children = $contentBody->content->all()[0]->children();
        $this->assertCount(3, $children);
        $this->assertSame(' ', $children[1]->content);
    }

    #[Test]
    public function parseRepairsMalformedMarkupWithoutThrowingOrWarning(): void
    {
        $warnings = new StringCollection();

        $contentBody = $this->parser->parse('<p>a</b>', $warnings);

        $this->assertSame([], $warnings->all());
        $this->assertCount(1, $contentBody->content->all());
    }

    #[Test]
    public function parseWarnsWhenMarkupCannotBePlaced(): void
    {
        $warnings = new StringCollection();

        $contentBody = $this->parser->parse('<td>los</td>', $warnings);

        $this->assertNotEmpty($warnings->all());
        $this->assertCount(1, $contentBody->content->all());
    }

    #[Test]
    public function parseDoesNotWarnAboutMathMlOrHtml5Elements(): void
    {
        $warnings = new StringCollection();

        $contentBody = $this->parser->parse('<math><mi>x</mi></math>', $warnings);

        $this->assertSame([], $warnings->all());
        $this->assertSame('math', $contentBody->content->all()[0]->tagName());
    }

    #[Test]
    public function parseRestoresTheLibxmlErrorBufferState(): void
    {
        libxml_use_internal_errors(false);

        $this->parser->parse('<p>a</b>');

        $this->assertFalse(libxml_use_internal_errors(false));
        $this->assertSame([], libxml_get_errors());
    }

    #[Test]
    public function parseOfADisallowedTagThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parser->parse('<script>x</script>');
    }

    #[Test]
    public function parseOfATagThatCannotStandOnItsOwnThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not allowed as direct child of a content body');

        $this->parser->parse('<li>los</li>');
    }

    #[Test]
    public function parseOfAMathMlElementThatIsNotTheRootThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not allowed as direct child of a content body');

        $this->parser->parse('<mi>x</mi>');
    }

    #[Test]
    public function parseDoesNotWarnAboutHtml5TagsLibxmlDoesNotKnow(): void
    {
        $warnings = new StringCollection();

        $contentBody = $this->parser->parse('<figure><figcaption>Fig. 1</figcaption></figure><section>x</section>', $warnings);

        $this->assertSame([], $warnings->all());
        $this->assertCount(2, $contentBody->content->all());
    }

    /** A `</body>` closes the parser's own wrapper, leaving the rest beside it. */
    #[Test]
    public function parseKeepsContentThatFollowsAClosingBodyTag(): void
    {
        $contentBody = $this->parser->parse('<p>een</p></body><p>twee</p>');

        $content = $contentBody->content->all();
        $this->assertCount(2, $content);
        $this->assertSame('een', (string) $content[0]->children()[0]);
        $this->assertSame('twee', (string) $content[1]->children()[0]);
    }

    #[Test]
    public function parseLeavesLibxmlErrorsTheCallerWasCollecting(): void
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadXML('<a><b></a>');
        $collected = count(libxml_get_errors());

        $this->parser->parse('<p>a</p>');

        $this->assertCount($collected, libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }

    #[Test]
    public function parseAppliesACallerSuppliedDirectChildRule(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not allowed as direct child of a content body');

        $this->parser->parse('<strong>vet</strong>', null, fn (string $tagName): bool => $tagName === 'p');
    }

    #[Test]
    public function parseAcceptsWhatACallerSuppliedDirectChildRuleAllows(): void
    {
        $contentBody = $this->parser->parse('<p>blok</p>', null, fn (string $tagName): bool => $tagName === 'p');

        $this->assertSame('p', $contentBody->content->all()[0]->tagName());
    }
}
