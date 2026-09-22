<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Service\QtiPackageBuilder;

use DOMDocument;
use Qti3\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use Qti3\AssessmentItem\Service\Parser\QtiExpressionParser;
use Qti3\AssessmentItem\Service\Parser\RubricBlockParser;
use Qti3\AssessmentTest\Service\Parser\AssessmentItemRefParser;
use Qti3\AssessmentTest\Service\Parser\AssessmentSectionParser;
use Qti3\AssessmentTest\Service\Parser\AssessmentTestParser;
use Qti3\AssessmentTest\Service\Parser\OutcomeProcessingParser;
use Qti3\AssessmentTest\Service\Parser\TestFeedbackParser;
use Qti3\AssessmentTest\Service\Parser\TestPartParser;
use Qti3\Package\Model\Manifest\ManifestResourceDependencyCollection;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Service\QtiPackageBuilder\TestResourceBuilder;
use Qti3\Shared\Xml\Builder\XmlBuilder;
use Qti3\Shared\Xml\Reader\XmlReader;
use Qti3\Tests\Unit\AssessmentTest\Model\AssessmentTestStub;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TestResourceBuilderTest extends TestCase
{
    private TestResourceBuilder $assessmentTestBuilder;
    private AssessmentTestParser $assessmentTestParser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assessmentTestBuilder = new TestResourceBuilder(new XmlBuilder(), new XmlReader());

        $itemRefParser = new AssessmentItemRefParser();
        $sectionParser = new AssessmentSectionParser($itemRefParser);
        $this->assessmentTestParser = new AssessmentTestParser(
            new OutcomeDeclarationParser(),
            new TestPartParser($sectionParser),
            new RubricBlockParser(),
            new OutcomeProcessingParser(new QtiExpressionParser()),
            new TestFeedbackParser(),
        );
    }

    #[Test]
    public function testBuild(): void
    {
        $assessmentTestResource = $this->assessmentTestBuilder->build(
            AssessmentTestStub::assessmentTest(),
            new ManifestResourceDependencyCollection(),
        );

        $this->assertInstanceOf(Resource::class, $assessmentTestResource);
        $this->assertStringContainsString(
            '<qti-assessment-test',
            (string) $assessmentTestResource->files->first()->getContent(),
        );
    }

    #[Test]
    public function buildKeepsXmlLangThroughAParseAndBuildRoundTrip(): void
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
        $assessmentTest = $this->assessmentTestParser->parse($dom->documentElement)->test;

        $assessmentTestResource = $this->assessmentTestBuilder->build(
            $assessmentTest,
            new ManifestResourceDependencyCollection(),
        );

        $this->assertStringContainsString(
            'xml:lang="nl"',
            (string) $assessmentTestResource->files->first()->getContent(),
        );
    }

    #[Test]
    public function buildOmitsXmlLangWhenTheTestHasNone(): void
    {
        $assessmentTestResource = $this->assessmentTestBuilder->build(
            AssessmentTestStub::assessmentTest(),
            new ManifestResourceDependencyCollection(),
        );

        $this->assertStringNotContainsString(
            'xml:lang',
            (string) $assessmentTestResource->files->first()->getContent(),
        );
    }
}
