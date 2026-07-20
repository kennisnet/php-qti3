<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentTest\Service;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentTest\Service\AssessmentTestSupportValidator;
use Qti3\Package\Exception\UnsupportedQtiConstructException;

final class AssessmentTestSupportValidatorTest extends TestCase
{
    private const string ASI_NAMESPACE = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';

    private AssessmentTestSupportValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new AssessmentTestSupportValidator();
    }

    #[Test]
    public function aSupportedTestPasses(): void
    {
        $this->validator->assertSupported($this->testElement(
            '<qti-outcome-declaration identifier="SCORE" cardinality="single" base-type="float"/>'
            . '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">'
            . '<qti-assessment-section identifier="s" title="" visible="true">'
            . '<qti-selection select="2"/><qti-ordering shuffle="true"/>'
            . '<qti-assessment-item-ref identifier="ITEM001" href="ITEM001.xml"/>'
            . '</qti-assessment-section></qti-test-part>',
        ));

        $this->assertTrue(true); // no exception thrown
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function unsupportedTests(): array
    {
        return [
            'outcome processing' => [
                '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">'
                . '<qti-assessment-section identifier="s" title="" visible="true"/></qti-test-part>'
                . '<qti-outcome-processing/>',
                'qti-outcome-processing',
            ],
            'test feedback' => [
                '<qti-test-feedback identifier="f" outcome-identifier="SCORE" show-hide="show"/>',
                'qti-test-feedback',
            ],
            'time limits on test part' => [
                '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">'
                . '<qti-time-limits max-time="60"/>'
                . '<qti-assessment-section identifier="s" title="" visible="true"/></qti-test-part>',
                'qti-time-limits',
            ],
            'nested sections' => [
                '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">'
                . '<qti-assessment-section identifier="outer" title="" visible="true">'
                . '<qti-assessment-section identifier="inner" title="" visible="true"/>'
                . '</qti-assessment-section></qti-test-part>',
                'nested sections',
            ],
            'rubric block in section' => [
                '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">'
                . '<qti-assessment-section identifier="s" title="" visible="true"><qti-rubric-block view="candidate"/></qti-assessment-section>'
                . '</qti-test-part>',
                'qti-rubric-block',
            ],
            'weight inside item ref' => [
                '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">'
                . '<qti-assessment-section identifier="s" title="" visible="true">'
                . '<qti-assessment-item-ref identifier="ITEM001" href="ITEM001.xml"><qti-weight identifier="W" value="2"/></qti-assessment-item-ref>'
                . '</qti-assessment-section></qti-test-part>',
                'ITEM001',
            ],
        ];
    }

    #[Test]
    #[DataProvider('unsupportedTests')]
    public function anUnsupportedConstructIsRefused(string $children, string $expectedMessagePart): void
    {
        try {
            $this->validator->assertSupported($this->testElement($children));
            $this->fail('Expected UnsupportedQtiConstructException');
        } catch (UnsupportedQtiConstructException $exception) {
            $this->assertStringContainsString($expectedMessagePart, (string) $exception->validationErrors()->join(' '));
        }
    }

    private function testElement(string $children): DOMElement
    {
        $dom = new DOMDocument();
        $dom->loadXML(sprintf(
            '<qti-assessment-test xmlns="%s" identifier="T" title="">%s</qti-assessment-test>',
            self::ASI_NAMESPACE,
            $children,
        ));

        $element = $dom->documentElement;
        $this->assertInstanceOf(DOMElement::class, $element);

        return $element;
    }
}
