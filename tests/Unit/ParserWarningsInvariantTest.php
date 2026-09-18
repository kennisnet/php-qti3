<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Downloader\Resource\IResourceDownloader;
use Qti3\Package\Service\IFilesystemPackageFactory;
use Qti3\Package\Validator\Resource\IResourceValidator;
use Qti3\QtiClient;

/**
 * Guards the core soundness invariant of the editing flow: the parsers report
 * (as warnings) exactly the constructs they cannot faithfully round-trip.
 *
 * - A supported construct parses without warnings and survives regeneration.
 * - A construct that would be lost raises a warning.
 *
 * This turns "silent data loss on regenerate" into a red build the moment a
 * parser is taught (or forgets) to keep a construct, without a hand-maintained
 * allowlist that can drift from the parser/model.
 */
final class ParserWarningsInvariantTest extends TestCase
{
    private QtiClient $client;

    protected function setUp(): void
    {
        $this->client = new QtiClient(
            $this->createStub(IFilesystemPackageFactory::class),
            $this->createStub(IResourceValidator::class),
            $this->createStub(IResourceDownloader::class),
        );
    }

    // --- assessment test ----------------------------------------------------

    /**
     * @param list<string> $survives
     */
    #[Test]
    #[DataProvider('supportedTestConstructs')]
    public function supportedTestConstructParsesWithoutWarningsAndSurvives(string $testXml, array $survives): void
    {
        $result = $this->client->getAssessmentTestParser()->parse($this->element($testXml));

        $this->assertSame([], $result->warnings->all(), 'Supported construct must not warn');

        $regenerated = $this->regenerate($result->test);
        foreach ($survives as $needle) {
            $this->assertStringContainsString($needle, $regenerated);
        }
    }

    #[Test]
    #[DataProvider('lossyTestConstructs')]
    public function lossyTestConstructRaisesAWarning(string $testXml, string $goneNeedle): void
    {
        $result = $this->client->getAssessmentTestParser()->parse($this->element($testXml));

        $this->assertNotSame([], $result->warnings->all(), 'A lossy construct must raise a warning');
        $this->assertStringNotContainsString($goneNeedle, $this->regenerate($result->test));
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function supportedTestConstructs(): array
    {
        return [
            'selection' => [
                self::testWithSection('<qti-selection select="2" with-replacement="true"/>'),
                ['qti-selection', 'select="2"'],
            ],
            'ordering' => [
                self::testWithSection('<qti-ordering shuffle="true"/>'),
                ['qti-ordering', 'shuffle="true"'],
            ],
            'item ref with category' => [
                self::testWithSection('<qti-assessment-item-ref identifier="I1" href="I1.xml" category="hard"/>'),
                ['qti-assessment-item-ref', 'category="hard"'],
            ],
            'outcome declaration' => [
                self::test('<qti-outcome-declaration identifier="SCORE" cardinality="single" base-type="float"/>'
                    . self::testPart('')),
                ['qti-outcome-declaration', 'SCORE'],
            ],
            'rubric block' => [
                self::test(self::rubricBlock('candidate') . self::testPart('')),
                ['qti-rubric-block', 'use="instructions"', 'view="candidate"', 'qti-rubric-discretionary-placement', 'Welkom'],
            ],
            'two rubric blocks' => [
                self::test(self::rubricBlock('candidate') . self::rubricBlock('scorer', 'Nakijkmodel') . self::testPart('')),
                ['view="candidate"', 'view="scorer"', 'Welkom', 'Nakijkmodel'],
            ],
            'rubric block with a view list' => [
                self::test(self::rubricBlock('candidate scorer') . self::testPart('')),
                ['view="candidate scorer"', 'Welkom'],
            ],
            'rubric block without a use' => [
                self::test(self::rubricBlock('candidate', use: null) . self::testPart('')),
                ['qti-rubric-block', 'view="candidate"', 'Welkom'],
            ],
            'outcome processing as Wikiwijs Maken writes it' => [
                self::test(self::testPart('') . self::outcomeProcessing(
                    '<qti-set-outcome-value identifier="MAX_SCORE"><qti-sum><qti-test-variables variable-identifier="MAXSCORE"/></qti-sum></qti-set-outcome-value>'
                    . '<qti-set-outcome-value identifier="SCORE"><qti-sum><qti-test-variables variable-identifier="SCORE"/></qti-sum></qti-set-outcome-value>'
                    . '<qti-outcome-condition>'
                    . '<qti-outcome-if><qti-gt><qti-product><qti-divide><qti-variable identifier="SCORE"/><qti-variable identifier="MAX_SCORE"/></qti-divide><qti-base-value base-type="float">100</qti-base-value></qti-product><qti-base-value base-type="float">55</qti-base-value></qti-gt>'
                    . '<qti-set-outcome-value identifier="PASS"><qti-base-value base-type="boolean">true</qti-base-value></qti-set-outcome-value></qti-outcome-if>'
                    . '<qti-outcome-else><qti-set-outcome-value identifier="PASS"><qti-base-value base-type="boolean">false</qti-base-value></qti-set-outcome-value></qti-outcome-else>'
                    . '</qti-outcome-condition>',
                )),
                ['qti-outcome-processing', 'qti-test-variables variable-identifier="MAXSCORE"', 'qti-outcome-if', 'qti-gt', 'qti-divide', '>55<', 'qti-outcome-else', 'identifier="PASS"'],
            ],
            'outcome processing with every rule kind and a test-variables subset' => [
                self::test(self::testPart('') . self::outcomeProcessing(
                    '<qti-outcome-condition>'
                    . '<qti-outcome-if><qti-lt><qti-variable identifier="SCORE"/><qti-base-value base-type="float">1</qti-base-value></qti-lt>'
                    . '<qti-set-outcome-value identifier="A"><qti-base-value base-type="integer">1</qti-base-value></qti-set-outcome-value>'
                    . '<qti-set-outcome-value identifier="B"><qti-base-value base-type="integer">2</qti-base-value></qti-set-outcome-value>'
                    . '<qti-exit-test/></qti-outcome-if>'
                    . '<qti-outcome-else-if><qti-lt><qti-variable identifier="SCORE"/><qti-base-value base-type="float">2</qti-base-value></qti-lt>'
                    . '<qti-outcome-condition><qti-outcome-if><qti-is-null><qti-variable identifier="X"/></qti-is-null></qti-outcome-if></qti-outcome-condition></qti-outcome-else-if>'
                    . '<qti-outcome-else/>'
                    . '</qti-outcome-condition>'
                    . '<qti-lookup-outcome-value identifier="GRADE"><qti-test-variables variable-identifier="SCORE" section-identifier="S1" include-category="core" exclude-category="skip" weight-identifier="W" base-type="float"/></qti-lookup-outcome-value>',
                )),
                ['identifier="A"', 'identifier="B"', 'qti-exit-test', 'qti-outcome-else-if', '<qti-outcome-else/>', 'qti-lookup-outcome-value identifier="GRADE"', 'section-identifier="S1"', 'include-category="core"', 'exclude-category="skip"', 'weight-identifier="W"', 'base-type="float"'],
            ],
            'test feedback' => [
                self::test(self::testPart('') . '<qti-test-feedback identifier="F1" outcome-identifier="PASS" show-hide="hide" access="during" title="Resultaat"><qti-content-body><p>Goed <strong>gedaan</strong></p></qti-content-body></qti-test-feedback>'),
                ['qti-test-feedback', 'identifier="F1"', 'outcome-identifier="PASS"', 'show-hide="hide"', 'access="during"', 'title="Resultaat"', '<strong>gedaan</strong>'],
            ],
            'test feedback without a content body wrapper' => [
                self::test(self::testPart('') . '<qti-test-feedback identifier="F1" outcome-identifier="PASS"><p>Goed</p></qti-test-feedback>'),
                ['qti-test-feedback', 'show-hide="show"', 'access="atEnd"', '<p>Goed</p>'],
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function lossyTestConstructs(): array
    {
        return [
            'outcome rule with an expression the model does not know' => [
                self::test(self::testPart('') . self::outcomeProcessing('<qti-set-outcome-value identifier="N"><qti-number-correct/></qti-set-outcome-value>')),
                'qti-number-correct',
            ],
            'unknown outcome rule' => [
                self::test(self::testPart('') . self::outcomeProcessing('<qti-outcome-rule-ext identifier="N"/>')),
                'qti-outcome-rule-ext',
            ],
            'outcome condition without an if' => [
                self::test(self::testPart('') . self::outcomeProcessing('<qti-outcome-condition><qti-outcome-else/></qti-outcome-condition>')),
                'qti-outcome-condition',
            ],
            'second outcome processing' => [
                self::test(self::testPart('') . self::outcomeProcessing('<qti-exit-test/>') . self::outcomeProcessing('<qti-set-outcome-value identifier="TWICE"><qti-base-value base-type="integer">1</qti-base-value></qti-set-outcome-value>')),
                'TWICE',
            ],
            'test feedback with an unknown attribute' => [
                self::test(self::testPart('') . '<qti-test-feedback identifier="F1" outcome-identifier="PASS" data-x="y"><p>Goed</p></qti-test-feedback>'),
                'data-x',
            ],
            'nested section' => [
                self::testWithSection('<qti-assessment-section identifier="inner" title="" visible="true"/>'),
                'identifier="inner"',
            ],
            'unknown test attribute' => [
                self::test(self::testPart(''), extraTestAttribute: 'tool-name="x"'),
                'tool-name="x"',
            ],
            'unknown section attribute' => [
                self::testWithSection('', extraSectionAttribute: 'keep-together="true"'),
                'keep-together="true"',
            ],
            'rubric block with an extension use' => [
                self::test(self::rubricBlock('candidate', use: 'ext:hint') . self::testPart('')),
                'ext:hint',
            ],
            'rubric block with an unknown view' => [
                self::test(self::rubricBlock('candidate reviewer') . self::testPart('')),
                'reviewer',
            ],
            'item ref with child' => [
                self::testWithSection('<qti-assessment-item-ref identifier="I1" href="I1.xml"><qti-weight identifier="W" value="2"/></qti-assessment-item-ref>'),
                'qti-weight',
            ],
        ];
    }

    #[Test]
    public function warningLocatesTheOffendingElementByLineAndSelector(): void
    {
        $result = $this->client->getAssessmentTestParser()->parse(
            $this->element(self::testWithSection('<qti-selection select="2"/>', extraSectionAttribute: 'keep-together="true"')),
        );

        $warning = $result->warnings->all()[0];
        $this->assertMatchesRegularExpression('/^line \d+ at \//', $warning);
        $this->assertStringContainsString("/qti-assessment-section[@identifier='s']", $warning);
        $this->assertStringContainsString('keep-together', $warning);
    }

    #[Test]
    public function parseFromStringPrefixesWarningsWithTheGivenSource(): void
    {
        $result = $this->client->getAssessmentItemParser()->parseFromString(
            self::item('<qti-template-declaration identifier="T" cardinality="single" base-type="integer"/><qti-item-body><p>x</p></qti-item-body>'),
            'ITEM001.xml',
        );

        $this->assertNotSame([], $result->warnings->all());
        foreach ($result->warnings as $warning) {
            $this->assertStringStartsWith('ITEM001.xml: line ', $warning);
        }
    }

    // --- assessment item ----------------------------------------------------

    #[Test]
    public function supportedItemParsesWithoutWarnings(): void
    {
        $result = $this->client->getAssessmentItemParser()->parse($this->element(
            '<qti-assessment-item identifier="I1" title="t" time-dependent="false">'
            . '<qti-stylesheet href="style.css"/>'
            . '<qti-item-body><p>Vraag</p></qti-item-body>'
            . '</qti-assessment-item>',
        ));

        $this->assertSame([], $result->warnings->all());
    }

    #[Test]
    public function itemLevelRubricBlockWithAViewListParsesWithoutWarningsAndSurvives(): void
    {
        $result = $this->client->getAssessmentItemParser()->parse($this->element(
            self::item('<qti-item-body>' . self::rubricBlock('candidate scorer', use: null) . '</qti-item-body>'),
        ));

        $this->assertSame([], $result->warnings->all());

        $regenerated = $this->regenerate($result->item);
        $this->assertStringContainsString('view="candidate scorer"', $regenerated);
        $this->assertStringNotContainsString('use=', $regenerated);
    }

    #[Test]
    #[DataProvider('lossyItemConstructs')]
    public function lossyItemConstructRaisesAWarning(string $itemXml): void
    {
        $result = $this->client->getAssessmentItemParser()->parse($this->element($itemXml));

        $this->assertNotSame([], $result->warnings->all());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function lossyItemConstructs(): array
    {
        return [
            'template declaration' => [
                self::item('<qti-template-declaration identifier="T" cardinality="single" base-type="integer"/><qti-item-body><p>x</p></qti-item-body>'),
            ],
            'two stylesheets' => [
                self::item('<qti-stylesheet href="a.css"/><qti-stylesheet href="b.css"/><qti-item-body><p>x</p></qti-item-body>'),
            ],
            'unknown item attribute' => [
                self::item('<qti-item-body><p>x</p></qti-item-body>', extraItemAttribute: 'label="x"'),
            ],
            'rubric block with an extension use' => [
                self::item('<qti-item-body>' . self::rubricBlock('candidate', use: 'ext:hint') . '</qti-item-body>'),
            ],
            'rubric block with an unknown view' => [
                self::item('<qti-item-body>' . self::rubricBlock('candidate reviewer') . '</qti-item-body>'),
            ],
        ];
    }

    // --- helpers -------------------------------------------------------------

    private function regenerate(object $model): string
    {
        return (string) $this->client->getXmlBuilder()->generateXmlFromObject($model)->saveXML();
    }

    private function element(string $xml): DOMElement
    {
        $document = new DOMDocument();
        $document->loadXML($xml);
        self::assertInstanceOf(DOMElement::class, $document->documentElement);

        return $document->documentElement;
    }

    private static function test(string $body, string $extraTestAttribute = ''): string
    {
        return sprintf('<qti-assessment-test identifier="T" title="t" %s>%s</qti-assessment-test>', $extraTestAttribute, $body);
    }

    private static function testPart(string $sections): string
    {
        return sprintf(
            '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">%s</qti-test-part>',
            $sections,
        );
    }

    private static function outcomeProcessing(string $rules): string
    {
        return sprintf('<qti-outcome-processing>%s</qti-outcome-processing>', $rules);
    }

    private static function rubricBlock(string $view, string $text = 'Welkom', ?string $use = 'instructions'): string
    {
        return sprintf(
            '<qti-rubric-block %s view="%s" class="qti-rubric-discretionary-placement">'
            . '<qti-content-body><p>%s</p></qti-content-body></qti-rubric-block>',
            $use === null ? '' : sprintf('use="%s"', $use),
            $view,
            $text,
        );
    }

    private static function testWithSection(string $sectionBody, string $extraSectionAttribute = ''): string
    {
        return self::test(self::testPart(sprintf(
            '<qti-assessment-section identifier="s" title="" visible="true" %s>%s</qti-assessment-section>',
            $extraSectionAttribute,
            $sectionBody,
        )));
    }

    private static function item(string $body, string $extraItemAttribute = ''): string
    {
        return sprintf('<qti-assessment-item identifier="I1" title="t" time-dependent="false" %s>%s</qti-assessment-item>', $extraItemAttribute, $body);
    }
}
