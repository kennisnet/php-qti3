<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentTest\Service\Parser;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Model\Feedback\Visibility;
use Qti3\AssessmentItem\Service\Parser\ParseError;
use Qti3\AssessmentTest\Model\Feedback\TestFeedbackAccess;
use Qti3\AssessmentTest\Service\Parser\TestFeedbackParser;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\TextNode;

final class TestFeedbackParserTest extends TestCase
{
    private TestFeedbackParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TestFeedbackParser();
    }

    #[Test]
    public function parsesEveryAttributeAndTheContentBody(): void
    {
        $warnings = new StringCollection();

        $feedback = $this->parser->parse($this->element(
            '<qti-test-feedback identifier="F1" outcome-identifier="PASS" show-hide="hide" access="during" title="Resultaat">'
            . '<qti-content-body><p>Goed <strong>gedaan</strong></p></qti-content-body>'
            . '</qti-test-feedback>',
        ), $warnings);

        $this->assertSame([], $warnings->all());
        $this->assertSame('F1', $feedback->identifier);
        $this->assertSame('PASS', $feedback->outcomeIdentifier);
        $this->assertSame(Visibility::HIDE, $feedback->showHide);
        $this->assertSame(TestFeedbackAccess::DURING, $feedback->access);
        $this->assertSame('Resultaat', $feedback->title);
        $paragraph = $feedback->contentBody->content->all()[0];
        $this->assertInstanceOf(HTMLTag::class, $paragraph);
        $this->assertSame('p', $paragraph->tagName());
    }

    #[Test]
    public function defaultsShowHideAndAccessAndAcceptsContentWithoutAWrapper(): void
    {
        $feedback = $this->parser->parse($this->element('<qti-test-feedback identifier="F1" outcome-identifier="PASS"><p>Goed</p></qti-test-feedback>'));

        $this->assertSame(Visibility::SHOW, $feedback->showHide);
        $this->assertSame(TestFeedbackAccess::AT_END, $feedback->access);
        $this->assertNull($feedback->title);
        $this->assertCount(1, $feedback->contentBody->content);
    }

    #[Test]
    public function unknownEnumValuesFallBackToTheDefaultWithAWarning(): void
    {
        $warnings = new StringCollection();

        $feedback = $this->parser->parse($this->element('<qti-test-feedback identifier="F1" outcome-identifier="PASS" show-hide="maybe" access="later"/>'), $warnings);

        $this->assertSame(Visibility::SHOW, $feedback->showHide);
        $this->assertSame(TestFeedbackAccess::AT_END, $feedback->access);
        $this->assertCount(2, $warnings);
        $this->assertStringContainsString('defaults unknown access "later" to "atEnd"', $warnings->all()[0]);
        $this->assertStringContainsString('defaults unknown show-hide "maybe" to "show"', $warnings->all()[1]);
    }

    #[Test]
    public function anUnknownAttributeIsReported(): void
    {
        $warnings = new StringCollection();

        $this->parser->parse($this->element('<qti-test-feedback identifier="F1" outcome-identifier="PASS" data-x="y"/>'), $warnings);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('drops unsupported attribute "data-x"', $warnings->all()[0]);
    }

    #[Test]
    public function aChildThatIsNotContentIsDroppedWithAWarningInsteadOfFailingTheParse(): void
    {
        // Schema-valid: qti-stylesheet and qti-catalog-info are siblings of the content, not part of it.
        $warnings = new StringCollection();

        $feedback = $this->parser->parse($this->element(
            '<qti-test-feedback identifier="F1" outcome-identifier="PASS"><qti-stylesheet href="a.css" type="text/css"/><p>Goed</p></qti-test-feedback>',
        ), $warnings);

        $this->assertCount(1, $feedback->contentBody->content);
        $this->assertSame(1, count($warnings));
        $this->assertStringContainsString('drops unsupported element <qti-stylesheet>, Invalid HTML tag name: qti-stylesheet', $warnings->all()[0]);
    }

    #[Test]
    public function withAWrapperItsSiblingsAndItsAttributesAreReportedOnce(): void
    {
        $warnings = new StringCollection();

        $feedback = $this->parser->parse($this->element(
            '<qti-test-feedback identifier="F1" outcome-identifier="PASS">'
            . '<qti-stylesheet href="a.css" type="text/css"/>'
            . '<qti-content-body id="body" class="x"><p>Goed</p></qti-content-body>'
            . '</qti-test-feedback>',
        ), $warnings);

        $this->assertCount(1, $feedback->contentBody->content);
        $this->assertSame([
            'drops unsupported element <qti-stylesheet>',
            'drops unsupported attribute "id"',
            'drops unsupported attribute "class"',
        ], array_map(static fn(string $warning): string => substr($warning, (int) strpos($warning, ': ') + 2), $warnings->all()));
    }

    #[Test]
    public function aTagOutsideTheContentModelIsKeptWithASingleWarning(): void
    {
        $warnings = new StringCollection();

        $feedback = $this->parser->parse($this->element('<qti-test-feedback identifier="F1" outcome-identifier="PASS"><li>x</li></qti-test-feedback>'), $warnings);

        $this->assertCount(1, $feedback->contentBody->content);
        $this->assertSame(1, count($warnings));
        $this->assertStringContainsString('keeps <li>, which the content model does not allow here', $warnings->all()[0]);
    }

    #[Test]
    public function inlineContentAndIndentationAreParsedAsAuthored(): void
    {
        $warnings = new StringCollection();

        $feedback = $this->parser->parse($this->element(
            "<qti-test-feedback identifier=\"F1\" outcome-identifier=\"PASS\">\n    Goed <strong>gedaan</strong>\n</qti-test-feedback>",
        ), $warnings);

        $this->assertSame([], $warnings->all());
        $nodes = $feedback->contentBody->content->all();
        $this->assertCount(2, $nodes);
        $this->assertInstanceOf(TextNode::class, $nodes[0]);
        $this->assertInstanceOf(HTMLTag::class, $nodes[1]);
        $this->assertSame('strong', $nodes[1]->tagName());
    }

    #[Test]
    public function rejectsAnotherElement(): void
    {
        $this->expectException(ParseError::class);

        $this->parser->parse($this->element('<qti-feedback-block identifier="F1"/>'));
    }

    private function element(string $xml): DOMElement
    {
        $document = new DOMDocument();
        $document->loadXML($xml);
        self::assertInstanceOf(DOMElement::class, $document->documentElement);

        return $document->documentElement;
    }
}
