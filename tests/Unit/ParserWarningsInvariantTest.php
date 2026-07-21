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
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function lossyTestConstructs(): array
    {
        return [
            'outcome processing' => [
                self::test(self::testPart('') . '<qti-outcome-processing/>'),
                'qti-outcome-processing',
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
