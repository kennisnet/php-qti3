<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Service;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Service\AssessmentItemSupportValidator;
use Qti3\Shared\Exception\UnsupportedQtiConstructException;

final class AssessmentItemSupportValidatorTest extends TestCase
{
    private const string ASI_NAMESPACE = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';

    private AssessmentItemSupportValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new AssessmentItemSupportValidator();
    }

    #[Test]
    public function aSupportedItemPasses(): void
    {
        $this->validator->assertSupported($this->itemElement(
            '<qti-response-declaration identifier="RESPONSE" cardinality="single" base-type="identifier"/>'
            . '<qti-outcome-declaration identifier="SCORE" cardinality="single" base-type="float"/>'
            . '<qti-stylesheet href="style.css" type="text/css"/>'
            . '<qti-item-body><p>Vraag</p></qti-item-body>'
            . '<qti-response-processing template="https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/match_correct.xml"/>'
            . '<qti-modal-feedback identifier="fb" outcome-identifier="FEEDBACK" show-hide="show"/>',
        ));

        $this->assertTrue(true); // no exception thrown
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function unsupportedItems(): array
    {
        return [
            'template declaration' => [
                '<qti-template-declaration identifier="T1" cardinality="single" base-type="integer"/>'
                . '<qti-item-body><p>Vraag</p></qti-item-body>',
                'qti-template-declaration',
            ],
            'template processing' => [
                '<qti-template-processing/><qti-item-body><p>Vraag</p></qti-item-body>',
                'qti-template-processing',
            ],
            'multiple stylesheets' => [
                '<qti-stylesheet href="a.css" type="text/css"/><qti-stylesheet href="b.css" type="text/css"/>'
                . '<qti-item-body><p>Vraag</p></qti-item-body>',
                'more than one stylesheet',
            ],
            'unknown response processing template' => [
                '<qti-item-body><p>Vraag</p></qti-item-body>'
                . '<qti-response-processing template="https://example.com/custom.xml"/>',
                'https://example.com/custom.xml',
            ],
        ];
    }

    #[Test]
    #[DataProvider('unsupportedItems')]
    public function anUnsupportedConstructIsRefused(string $children, string $expectedMessagePart): void
    {
        try {
            $this->validator->assertSupported($this->itemElement($children));
            $this->fail('Expected UnsupportedQtiConstructException');
        } catch (UnsupportedQtiConstructException $exception) {
            $this->assertStringContainsString($expectedMessagePart, (string) $exception->validationErrors()->join(' '));
        }
    }

    private function itemElement(string $children): DOMElement
    {
        $dom = new DOMDocument();
        $dom->loadXML(sprintf(
            '<qti-assessment-item xmlns="%s" identifier="X" title="Vraag" time-dependent="false">%s</qti-assessment-item>',
            self::ASI_NAMESPACE,
            $children,
        ));

        $element = $dom->documentElement;
        $this->assertInstanceOf(DOMElement::class, $element);

        return $element;
    }
}
