<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentTest\Service\Parser;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentTest\Model\TestPart\NavigationMode;
use Qti3\AssessmentTest\Model\TestPart\SubmissionMode;
use Qti3\AssessmentTest\Service\Parser\AssessmentItemRefParser;
use Qti3\AssessmentTest\Service\Parser\AssessmentSectionParser;
use Qti3\AssessmentTest\Service\Parser\TestPartParser;
use Qti3\Shared\Collection\StringCollection;

final class TestPartParserTest extends TestCase
{
    private const string ASI_NAMESPACE = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';

    private TestPartParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TestPartParser(
            new AssessmentSectionParser(new AssessmentItemRefParser()),
        );
    }

    #[Test]
    public function aMissingNavigationModeDefaultsToLinearAndWarns(): void
    {
        $element = $this->testPartElement(navigationMode: null);
        $warnings = new StringCollection();

        $testPart = $this->parser->parse($element, $warnings);

        $this->assertSame(NavigationMode::LINEAR, $testPart->navigationMode);
        $this->assertCount(1, $warnings->all());
        $this->assertStringContainsString('navigation-mode', $warnings->all()[0]);
    }

    #[Test]
    public function anUnknownSubmissionModeDefaultsToIndividualAndWarns(): void
    {
        $element = $this->testPartElement(submissionMode: 'weird');
        $warnings = new StringCollection();

        $testPart = $this->parser->parse($element, $warnings);

        $this->assertSame(SubmissionMode::INDIVIDUAL, $testPart->submissionMode);
        $this->assertCount(1, $warnings->all());
        $this->assertStringContainsString('"weird"', $warnings->all()[0]);
    }

    #[Test]
    public function validNavigationAndSubmissionModesRaiseNoWarning(): void
    {
        $element = $this->testPartElement(navigationMode: 'nonlinear', submissionMode: 'simultaneous');
        $warnings = new StringCollection();

        $testPart = $this->parser->parse($element, $warnings);

        $this->assertSame(NavigationMode::NONLINEAR, $testPart->navigationMode);
        $this->assertSame(SubmissionMode::SIMULTANEOUS, $testPart->submissionMode);
        $this->assertSame([], $warnings->all());
    }

    private function testPartElement(?string $navigationMode = 'linear', ?string $submissionMode = 'individual'): DOMElement
    {
        $navigationModeAttribute = $navigationMode === null ? '' : sprintf(' navigation-mode="%s"', $navigationMode);
        $submissionModeAttribute = $submissionMode === null ? '' : sprintf(' submission-mode="%s"', $submissionMode);

        $xml = sprintf(
            '<qti-test-part xmlns="%s" identifier="tp"%s%s>'
            . '<qti-assessment-section identifier="s" title="" visible="true"/>'
            . '</qti-test-part>',
            self::ASI_NAMESPACE,
            $navigationModeAttribute,
            $submissionModeAttribute,
        );

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $element = $dom->documentElement;
        self::assertInstanceOf(DOMElement::class, $element);

        return $element;
    }
}
