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
use Qti3\AssessmentTest\Model\Section\AssessmentSection;
use Qti3\AssessmentTest\Model\TestPart\NavigationMode;
use Qti3\AssessmentTest\Model\TestPart\SubmissionMode;
use Qti3\AssessmentTest\Model\TestPart\TestPart;
use Qti3\AssessmentTest\Service\Parser\AssessmentItemRefParser;
use Qti3\AssessmentTest\Service\Parser\AssessmentSectionParser;
use Qti3\AssessmentTest\Service\Parser\AssessmentTestParser;
use Qti3\AssessmentTest\Service\Parser\TestPartParser;
use Qti3\Shared\Html\ContentNodeParser;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclaration;
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
            new RubricBlockParser(new ContentNodeParser())
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
}
