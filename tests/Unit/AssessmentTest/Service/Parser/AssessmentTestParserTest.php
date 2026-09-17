<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentTest\Service\Parser;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Model\RubricBlock\RubricBlock;
use Qti3\AssessmentItem\Model\RubricBlock\View;
use Qti3\AssessmentItem\Model\RubricBlock\qtiUse;
use Qti3\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use Qti3\AssessmentItem\Service\Parser\RubricBlockParser;
use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\AssessmentTest\Model\Feedback\TestFeedback;
use Qti3\AssessmentTest\Model\OutcomeProcessing\ExitTest;
use Qti3\AssessmentTest\Model\OutcomeProcessing\OutcomeCondition;
use Qti3\AssessmentTest\Model\Section\AssessmentSection;
use Qti3\AssessmentTest\Model\TestPart\NavigationMode;
use Qti3\AssessmentTest\Model\TestPart\SubmissionMode;
use Qti3\AssessmentTest\Model\TestPart\TestPart;
use Qti3\AssessmentTest\Service\Parser\AssessmentItemRefParser;
use Qti3\AssessmentTest\Service\Parser\AssessmentSectionParser;
use Qti3\AssessmentTest\Service\Parser\AssessmentTestParser;
use Qti3\AssessmentTest\Service\Parser\TestPartParser;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclaration;
use Qti3\Shared\Model\Processing\SetOutcomeValue;
use Qti3\Shared\Model\TextNode;
use DOMDocument;

class AssessmentTestParserTest extends TestCase
{
    private AssessmentTestParser $parser;

    protected function setUp(): void
    {
        $itemRefParser = new AssessmentItemRefParser();
        $sectionParser = new AssessmentSectionParser($itemRefParser);
        $testPartParser = new TestPartParser($sectionParser);
        $outcomeDeclarationParser = new OutcomeDeclarationParser();

        $this->parser = new AssessmentTestParser(
            $outcomeDeclarationParser,
            $testPartParser,
            new RubricBlockParser()
        );
    }

    public function testParseAssessmentTest(): void
    {
        $xml = <<<XML
<qti-assessment-test xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0" 
                identifier="f36d8995-1234-5678-1234-567812345678" 
                title="Test Title">
    <qti-outcome-declaration identifier="SCORE" cardinality="single" base-type="float">
        <qti-default-value>
            <qti-value>0</qti-value>
        </qti-default-value>
    </qti-outcome-declaration>
    <qti-test-part identifier="part1" navigation-mode="linear" submission-mode="individual">
        <qti-assessment-section identifier="section1" title="Section 1" visible="true">
            <qti-assessment-item-ref identifier="item1" href="item1.xml" />
            <qti-assessment-item-ref identifier="item2" href="item2.xml" category="easy" />
        </qti-assessment-section>
    </qti-test-part>
</qti-assessment-test>
XML;

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $assessmentTest = $this->parser->parse($dom->documentElement)->test;

        $this->assertInstanceOf(AssessmentTest::class, $assessmentTest);
        $this->assertEquals('f36d8995-1234-5678-1234-567812345678', (string) $assessmentTest->identifier);
        $this->assertEquals('Test Title', $assessmentTest->title);

        // Outcome Declarations
        $this->assertCount(1, $assessmentTest->outcomeDeclarations);
        $this->assertEquals('SCORE', $assessmentTest->outcomeDeclarations->all()[0]->identifier);

        // Test Parts
        $this->assertCount(1, $assessmentTest->testParts);
        /** @var TestPart $testPart */
        $testPart = $assessmentTest->testParts->all()[0];
        $this->assertEquals('part1', $testPart->identifier);
        $this->assertEquals(NavigationMode::LINEAR, $testPart->navigationMode);
        $this->assertEquals(SubmissionMode::INDIVIDUAL, $testPart->submissionMode);

        // Sections
        $this->assertCount(1, $testPart->sections);
        /** @var AssessmentSection $section */
        $section = $testPart->sections->all()[0];
        $this->assertEquals('section1', $section->identifier);
        $this->assertEquals('Section 1', $section->title);
        $this->assertTrue($section->visible);

        // Item Refs
        $this->assertCount(2, $section->assessmentItemRefs);
        $itemRef1 = $section->assessmentItemRefs->all()[0];
        $this->assertEquals('item1', (string) $itemRef1->identifier);
        $this->assertEquals('item1.xml', $itemRef1->href);
        $this->assertNull($itemRef1->category);

        $itemRef2 = $section->assessmentItemRefs->all()[1];
        $this->assertEquals('item2', (string) $itemRef2->identifier);
        $this->assertEquals('item2.xml', $itemRef2->href);
        $this->assertEquals('easy', $itemRef2->category);
    }

    #[Test]
    public function xmlLangIsParsedIntoTheModelWithoutAWarning(): void
    {
        $xml = <<<XML
<qti-assessment-test xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0" identifier="test-1" title="Toets" xml:lang="nl">
    <qti-test-part identifier="part1" navigation-mode="linear" submission-mode="individual">
        <qti-assessment-section identifier="section1" title="Section 1" visible="true"/>
    </qti-test-part>
</qti-assessment-test>
XML;

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $result = $this->parser->parse($dom->documentElement);

        $this->assertSame('nl', $result->test->language);
        $this->assertSame([], $result->warnings->all());
    }

    #[Test]
    public function aTestWithoutXmlLangYieldsANullLanguage(): void
    {
        $xml = <<<XML
<qti-assessment-test xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0" identifier="test-1" title="Toets">
    <qti-test-part identifier="part1" navigation-mode="linear" submission-mode="individual">
        <qti-assessment-section identifier="section1" title="Section 1" visible="true"/>
    </qti-test-part>
</qti-assessment-test>
XML;

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $this->assertNull($this->parser->parse($dom->documentElement)->test->language);
    }

    #[Test]
    public function testLevelRubricBlocksAreParsedInOrder(): void
    {
        $xml = <<<XML
<qti-assessment-test xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0" identifier="test-1" title="Toets">
    <qti-rubric-block use="instructions" view="candidate" class="qti-rubric-discretionary-placement">
        <qti-content-body><p>Welkom</p></qti-content-body>
    </qti-rubric-block>
    <qti-rubric-block use="scoring" view="scorer">
        <qti-content-body><p>Nakijkmodel</p></qti-content-body>
    </qti-rubric-block>
    <qti-test-part identifier="part1" navigation-mode="linear" submission-mode="individual">
        <qti-assessment-section identifier="section1" title="Section 1" visible="true"/>
    </qti-test-part>
</qti-assessment-test>
XML;

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $result = $this->parser->parse($dom->documentElement);

        $this->assertSame([], $result->warnings->all());
        $this->assertCount(2, $result->test->rubricBlocks);

        /** @var RubricBlock $first */
        $first = $result->test->rubricBlocks->all()[0];
        $this->assertSame(qtiUse::INSTRUCTIONS, $first->use);
        $this->assertSame([View::CANDIDATE], $first->views->all());
        $this->assertSame('qti-rubric-discretionary-placement', $first->class);
        $this->assertStringContainsString('Welkom', $this->textOf($first));

        /** @var RubricBlock $second */
        $second = $result->test->rubricBlocks->all()[1];
        $this->assertSame(qtiUse::SCORING, $second->use);
        $this->assertSame([View::SCORER], $second->views->all());
        $this->assertNull($second->class);
        $this->assertStringContainsString('Nakijkmodel', $this->textOf($second));
    }

    #[Test]
    public function testWithoutRubricBlocksYieldsAnEmptyCollection(): void
    {
        $xml = <<<XML
<qti-assessment-test xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0" identifier="test-1" title="Toets">
    <qti-test-part identifier="part1" navigation-mode="linear" submission-mode="individual">
        <qti-assessment-section identifier="section1" title="Section 1" visible="true"/>
    </qti-test-part>
</qti-assessment-test>
XML;

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $this->assertTrue($this->parser->parse($dom->documentElement)->test->rubricBlocks->isEmpty());
    }

    #[Test]
    public function rubricBlocksArePlacedBetweenOutcomeDeclarationsAndTestParts(): void
    {
        $xml = <<<XML
<qti-assessment-test xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0" identifier="test-1" title="Toets">
    <qti-outcome-declaration identifier="SCORE" cardinality="single" base-type="float"/>
    <qti-rubric-block use="instructions" view="candidate">
        <qti-content-body><p>Welkom</p></qti-content-body>
    </qti-rubric-block>
    <qti-test-part identifier="part1" navigation-mode="linear" submission-mode="individual">
        <qti-assessment-section identifier="section1" title="Section 1" visible="true"/>
    </qti-test-part>
</qti-assessment-test>
XML;

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $children = $this->parser->parse($dom->documentElement)->test->children();

        $this->assertInstanceOf(OutcomeDeclaration::class, $children[0]);
        $this->assertInstanceOf(RubricBlock::class, $children[1]);
        $this->assertInstanceOf(TestPart::class, $children[2]);
    }

    private function textOf(RubricBlock $rubricBlock): string
    {
        $text = '';
        foreach ($rubricBlock->contentBody->content as $node) {
            $text .= $this->render($node);
        }

        return $text;
    }

    private function render(mixed $node): string
    {
        if ($node instanceof TextNode) {
            return $node->content;
        }
        if ($node instanceof HTMLTag) {
            $text = '';
            foreach ($node->children() as $child) {
                $text .= $this->render($child);
            }

            return $text;
        }

        return '';
    }

    #[Test]
    public function outcomeProcessingAndTestFeedbackAreParsedIntoTheModelWithoutAWarning(): void
    {
        $xml = <<<XML
<qti-assessment-test xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0" identifier="test-1" title="Toets">
    <qti-outcome-declaration identifier="PASS" cardinality="single" base-type="boolean"/>
    <qti-test-part identifier="part1" navigation-mode="linear" submission-mode="individual">
        <qti-assessment-section identifier="section1" title="Section 1" visible="true"/>
    </qti-test-part>
    <qti-outcome-processing>
        <qti-set-outcome-value identifier="SCORE"><qti-sum><qti-test-variables variable-identifier="SCORE"/></qti-sum></qti-set-outcome-value>
        <qti-outcome-condition>
            <qti-outcome-if><qti-is-null><qti-variable identifier="SCORE"/></qti-is-null><qti-exit-test/></qti-outcome-if>
        </qti-outcome-condition>
    </qti-outcome-processing>
    <qti-test-feedback identifier="F1" outcome-identifier="PASS" show-hide="show" access="atEnd"><qti-content-body><p>Geslaagd</p></qti-content-body></qti-test-feedback>
    <qti-test-feedback identifier="F2" outcome-identifier="PASS" show-hide="hide" access="atEnd"><qti-content-body><p>Helaas</p></qti-content-body></qti-test-feedback>
</qti-assessment-test>
XML;

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $result = $this->parser->parse($dom->documentElement);

        $this->assertSame([], $result->warnings->all());
        $this->assertNotNull($result->test->outcomeProcessing);
        $this->assertCount(2, $result->test->outcomeProcessing->elements);
        $this->assertInstanceOf(SetOutcomeValue::class, $result->test->outcomeProcessing->elements[0]);
        $this->assertInstanceOf(OutcomeCondition::class, $result->test->outcomeProcessing->elements[1]);
        $this->assertSame(['F1', 'F2'], array_map(static fn(TestFeedback $feedback): string => $feedback->identifier, $result->test->testFeedback->all()));
    }

    #[Test]
    public function aTestWithoutOutcomeProcessingHasNone(): void
    {
        $xml = <<<XML
<qti-assessment-test xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0" identifier="test-1" title="Toets">
    <qti-test-part identifier="part1" navigation-mode="linear" submission-mode="individual">
        <qti-assessment-section identifier="section1" title="Section 1" visible="true"/>
    </qti-test-part>
</qti-assessment-test>
XML;

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $test = $this->parser->parse($dom->documentElement)->test;

        $this->assertNull($test->outcomeProcessing);
        $this->assertCount(0, $test->testFeedback);
    }

    #[Test]
    public function aSecondOutcomeProcessingIsDroppedWithAWarning(): void
    {
        $xml = <<<XML
<qti-assessment-test xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0" identifier="test-1" title="Toets">
    <qti-test-part identifier="part1" navigation-mode="linear" submission-mode="individual">
        <qti-assessment-section identifier="section1" title="Section 1" visible="true"/>
    </qti-test-part>
    <qti-outcome-processing><qti-exit-test/></qti-outcome-processing>
    <qti-outcome-processing><qti-set-outcome-value identifier="TWICE"><qti-base-value base-type="integer">1</qti-base-value></qti-set-outcome-value></qti-outcome-processing>
</qti-assessment-test>
XML;

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $result = $this->parser->parse($dom->documentElement);

        $this->assertNotNull($result->test->outcomeProcessing);
        $this->assertInstanceOf(ExitTest::class, $result->test->outcomeProcessing->elements[0]);
        $this->assertCount(1, $result->warnings);
        $this->assertStringContainsString('drops a second <qti-outcome-processing>', $result->warnings->all()[0]);
    }
}
