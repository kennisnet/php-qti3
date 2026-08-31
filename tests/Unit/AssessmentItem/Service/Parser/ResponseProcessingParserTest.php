<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Service\Parser;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Model\ResponseProcessing\ResponseCondition;
use Qti3\AssessmentItem\Model\ResponseProcessing\ResponseProcessing;
use Qti3\AssessmentItem\Service\Parser\ProcessingElementParser;
use Qti3\AssessmentItem\Service\Parser\QtiExpressionParser;
use Qti3\AssessmentItem\Service\Parser\ResponseProcessingParser;
use Qti3\Shared\Model\Processing\SetOutcomeValue;

class ResponseProcessingParserTest extends TestCase
{
    private ResponseProcessingParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ResponseProcessingParser(
            new ProcessingElementParser(new QtiExpressionParser()),
        );
    }

    private function loadElement(string $xml): DOMElement
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);

        /** @var DOMElement $element */
        $element = $doc->documentElement;
        return $element;
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function matchCorrectTemplateUrlProvider(): array
    {
        return [
            'v3 purl with extension' => ['https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/match_correct.xml'],
            'v3 purl without extension' => ['https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/match_correct'],
            'legacy url without extension' => ['http://www.imsglobal.org/question/qti_v2p1/rptemplates/match_correct'],
        ];
    }

    #[Test]
    #[DataProvider('matchCorrectTemplateUrlProvider')]
    public function parseResolvesMatchCorrectTemplateRegardlessOfUrlForm(string $template): void
    {
        $element = $this->loadElement(
            '<qti-response-processing template="' . $template . '"/>',
        );

        $result = $this->parser->parse($element);

        // The URL is re-emitted exactly as authored, so a round trip does not
        // rewrite the reference.
        $this->assertSame($template, $result->template);
        $this->assertCount(1, $result->elements);
        $this->assertInstanceOf(ResponseCondition::class, $result->elements[0]);
    }

    #[Test]
    public function parseResolvesMapResponseTemplateWithoutExtension(): void
    {
        $element = $this->loadElement(
            '<qti-response-processing template="https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/map_response"/>',
        );

        $result = $this->parser->parse($element);

        $this->assertSame(
            'https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/map_response',
            $result->template,
        );
        $this->assertInstanceOf(ResponseCondition::class, $result->elements[0]);
    }

    #[Test]
    public function parseResolvesMapResponsePointTemplateWithoutExtension(): void
    {
        $element = $this->loadElement(
            '<qti-response-processing template="https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/map_response_point"/>',
        );

        $result = $this->parser->parse($element);

        $this->assertSame(
            'https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/map_response_point',
            $result->template,
        );
        $this->assertInstanceOf(ResponseCondition::class, $result->elements[0]);
    }

    #[Test]
    public function parseTrimsWhitespaceAroundTheTemplateUrl(): void
    {
        $element = $this->loadElement(
            '<qti-response-processing template=" https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/match_correct "/>',
        );

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(ResponseCondition::class, $result->elements[0]);
    }

    #[Test]
    public function parseFallsBackToChildElementsForAnUnknownTemplate(): void
    {
        $element = $this->loadElement('
            <qti-response-processing template="https://example.org/rptemplates/custom_template">
                <qti-set-outcome-value identifier="SCORE">
                    <qti-base-value base-type="float">1</qti-base-value>
                </qti-set-outcome-value>
            </qti-response-processing>
        ');

        $result = $this->parser->parse($element);

        $this->assertNull($result->template);
        $this->assertCount(1, $result->elements);
        $this->assertInstanceOf(SetOutcomeValue::class, $result->elements[0]);
    }

    #[Test]
    public function parseWithoutTemplateUsesChildElements(): void
    {
        $element = $this->loadElement('
            <qti-response-processing>
                <qti-set-outcome-value identifier="SCORE">
                    <qti-base-value base-type="float">1</qti-base-value>
                </qti-set-outcome-value>
            </qti-response-processing>
        ');

        $result = $this->parser->parse($element);

        $this->assertNull($result->template);
        $this->assertInstanceOf(SetOutcomeValue::class, $result->elements[0]);
    }

    #[Test]
    public function templateNameIgnoresUnrelatedUrls(): void
    {
        $this->assertNull(ResponseProcessing::templateName(''));
        $this->assertNull(ResponseProcessing::templateName('match_correct'));
        $this->assertNull(ResponseProcessing::templateName('https://example.org/templates/match_correct'));
    }
}
