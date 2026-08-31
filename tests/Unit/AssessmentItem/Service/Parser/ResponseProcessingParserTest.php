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

        // Whatever the authored spelling, the reference is normalised to the
        // canonical template URL.
        $this->assertSame(ResponseProcessing::TEMPLATE_MATCH_CORRECT, $result->template);
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

        $this->assertSame(ResponseProcessing::TEMPLATE_MAP_RESPONSE, $result->template);
        $this->assertInstanceOf(ResponseCondition::class, $result->elements[0]);
    }

    #[Test]
    public function parseResolvesMapResponsePointTemplateWithoutExtension(): void
    {
        $element = $this->loadElement(
            '<qti-response-processing template="https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/map_response_point"/>',
        );

        $result = $this->parser->parse($element);

        $this->assertSame(ResponseProcessing::TEMPLATE_MAP_RESPONSE_POINT, $result->template);
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

    /**
     * Whichever spelling is authored, the emitted reference is always the
     * canonical `.xml` template URL.
     */
    #[Test]
    #[DataProvider('matchCorrectTemplateUrlProvider')]
    public function parsedTemplateUrlAlwaysEndsWithTheXmlExtension(string $template): void
    {
        $element = $this->loadElement(
            '<qti-response-processing template="' . $template . '"/>',
        );

        $result = $this->parser->parse($element);

        $this->assertNotNull($result->template);
        $this->assertStringEndsWith('.xml', $result->template);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function legacyV2p1TemplateUrlProvider(): array
    {
        return [
            'match_correct' => [
                'http://www.imsglobal.org/question/qti_v2p1/rptemplates/match_correct',
                ResponseProcessing::TEMPLATE_MATCH_CORRECT,
            ],
            'map_response' => [
                'http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_response',
                ResponseProcessing::TEMPLATE_MAP_RESPONSE,
            ],
            'map_response_point' => [
                'http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_response_point',
                ResponseProcessing::TEMPLATE_MAP_RESPONSE_POINT,
            ],
            'match_correct with extension' => [
                'http://www.imsglobal.org/question/qti_v2p1/rptemplates/match_correct.xml',
                ResponseProcessing::TEMPLATE_MATCH_CORRECT,
            ],
        ];
    }

    /**
     * A legacy v2p1 template URL refers to the same processing as its v3p0
     * counterpart, so it is rewritten to the v3p0 URL.
     */
    #[Test]
    #[DataProvider('legacyV2p1TemplateUrlProvider')]
    public function parseRewritesLegacyV2p1TemplateUrlsToV3p0(string $template, string $expected): void
    {
        $element = $this->loadElement(
            '<qti-response-processing template="' . $template . '"/>',
        );

        $result = $this->parser->parse($element);

        $this->assertSame($expected, $result->template);
        $this->assertStringContainsString('/v3p0/', (string) $result->template);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unrelatedTemplateUrlProvider(): array
    {
        return [
            'empty' => [''],
            'bare template name' => ['match_correct'],
            'other template directory' => ['https://example.org/templates/match_correct'],
        ];
    }

    #[Test]
    #[DataProvider('unrelatedTemplateUrlProvider')]
    public function parseIgnoresUnrelatedTemplateUrls(string $template): void
    {
        $element = $this->loadElement('
            <qti-response-processing template="' . $template . '">
                <qti-set-outcome-value identifier="SCORE">
                    <qti-base-value base-type="float">1</qti-base-value>
                </qti-set-outcome-value>
            </qti-response-processing>
        ');

        $result = $this->parser->parse($element);

        $this->assertNull($result->template);
        $this->assertInstanceOf(SetOutcomeValue::class, $result->elements[0]);
    }
}
