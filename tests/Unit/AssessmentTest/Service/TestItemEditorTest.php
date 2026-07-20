<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentTest\Service;

use DOMDocument;
use DOMElement;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Downloader\Resource\IResourceDownloader;
use Qti3\AssessmentItem\Exception\InvalidAssessmentItemException;
use Qti3\AssessmentTest\Exception\InvalidItemOrderException;
use Qti3\AssessmentTest\Exception\InvalidAssessmentTestException;
use Qti3\Package\Exception\InvalidQtiPackageException;
use Qti3\Shared\Exception\UnsupportedQtiConstructException;
use Qti3\Package\Filesystem\FlysystemPackageFactory;
use Qti3\AssessmentTest\Model\IItemEditor;
use Qti3\Package\Validator\Resource\IResourceValidator;
use Qti3\QtiClient;
use Qti3\Shared\Exception\ResourceNotFoundException;

final class TestItemEditorTest extends TestCase
{
    private const string FOLDER = 'qti/v1';
    private const string ASI_NAMESPACE = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';
    private const string MANIFEST_NAMESPACE = 'http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1';

    private FilesystemOperator $filesystem;
    private IItemEditor $editor;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());

        $client = new QtiClient(
            new FlysystemPackageFactory($this->filesystem),
            $this->createStub(IResourceValidator::class),
            $this->createStub(IResourceDownloader::class),
        );
        $this->editor = $client->getItemEditor(self::FOLDER);
    }

    #[Test]
    public function addItemAssignsTheNextIdentifierAndWritesTheItemFile(): void
    {
        $this->seedEmptyDraft();

        $item = $this->editor->addItem($this->itemXml('PLACEHOLDER'));

        $this->assertSame('ITEM001', $item->identifier);
        $stored = $this->read('ITEM001.xml');
        $this->assertStringContainsString('identifier="ITEM001"', $stored);
        $this->assertStringNotContainsString('PLACEHOLDER', $stored);
        $this->assertSame($stored, (string) $item->getMainFile());
    }

    #[Test]
    public function addItemContinuesNumberingFromExistingItems(): void
    {
        $this->seedEmptyDraft();
        $this->editor->addItem($this->itemXml('X'));

        $second = $this->editor->addItem($this->itemXml('X'));

        $this->assertSame('ITEM002', $second->identifier);
    }

    #[Test]
    public function addItemKeepsTimeDependentAndLanguage(): void
    {
        $this->seedEmptyDraft();

        $this->editor->addItem($this->itemXml('X', timeDependent: 'true', language: 'fr-FR'));

        $stored = $this->read('ITEM001.xml');
        $this->assertStringContainsString('time-dependent="true"', $stored);
        $this->assertStringContainsString('xml:lang="fr-FR"', $stored);
    }

    #[Test]
    public function addItemRegistersTheResourceInsideManifestResources(): void
    {
        $this->seedEmptyDraft();

        $this->editor->addItem($this->itemXml('X'));

        $resource = $this->findElement($this->read('imsmanifest.xml'), self::MANIFEST_NAMESPACE, 'resource', 'ITEM001');
        $this->assertInstanceOf(DOMElement::class, $resource);
        $this->assertSame('imsqti_item_xmlv3p0', $resource->getAttribute('type'));
        $this->assertSame('ITEM001.xml', $resource->getAttribute('href'));
        $this->assertSame('resources', $resource->parentNode?->localName);

        $file = null;
        foreach ($resource->getElementsByTagNameNS(self::MANIFEST_NAMESPACE, 'file') as $fileElement) {
            if ($fileElement->getAttribute('href') === 'ITEM001.xml') {
                $file = $fileElement;
            }
        }
        $this->assertInstanceOf(DOMElement::class, $file);
    }

    #[Test]
    public function addItemAppendsAnItemRefToTheAssessmentTestSection(): void
    {
        $this->seedEmptyDraft();

        $this->editor->addItem($this->itemXml('X'));

        $itemRef = $this->findElement($this->read('AssessmentTest.xml'), self::ASI_NAMESPACE, 'qti-assessment-item-ref', 'ITEM001');
        $this->assertInstanceOf(DOMElement::class, $itemRef);
        $this->assertSame('ITEM001.xml', $itemRef->getAttribute('href'));
        $this->assertSame('qti-assessment-section', $itemRef->parentNode?->localName);
    }

    #[Test]
    public function addItemKeepsTheAssessmentTestFileAtTheManifestHref(): void
    {
        $this->seedEmptyDraft(testHref: 'test/Main.xml');

        $this->editor->addItem($this->itemXml('X'));

        $this->assertStringContainsString('qti-assessment-item-ref', $this->read('test/Main.xml'));
    }

    #[Test]
    public function updateItemReplacesTheItemAndLeavesManifestAndTestEquivalent(): void
    {
        $this->seedEmptyDraft();
        $this->editor->addItem($this->itemXml('X'));
        $manifestBefore = $this->read('imsmanifest.xml');
        $testBefore = $this->read('AssessmentTest.xml');

        $this->editor->updateItem('ITEM001', $this->itemXml('ITEM001', 'Bijgewerkte vraag'));

        $this->assertStringContainsString('Bijgewerkte vraag', $this->read('ITEM001.xml'));
        $this->assertXmlStringEqualsXmlString($manifestBefore, $this->read('imsmanifest.xml'));
        $this->assertXmlStringEqualsXmlString($testBefore, $this->read('AssessmentTest.xml'));
    }

    #[Test]
    public function updateItemThrowsWhenTheItemDoesNotExist(): void
    {
        $this->seedEmptyDraft();

        $this->expectException(ResourceNotFoundException::class);

        $this->editor->updateItem('ITEM999', $this->itemXml('ITEM999'));
    }

    #[Test]
    public function updateItemReportsAMissingItemEvenWhenTheXmlIsInvalid(): void
    {
        $this->seedEmptyDraft();

        // Existence is checked before validation, so a missing item wins over malformed XML.
        $this->expectException(ResourceNotFoundException::class);

        $this->editor->updateItem('ITEM999', '<not-an-item/>');
    }

    #[Test]
    public function updateItemCanReplaceAnItemWithUnsupportedContent(): void
    {
        $this->seedEmptyDraft();
        $this->editor->addItem($this->itemXml('X'));
        // Corrupt ITEM001 with an interaction type the typed parser does not support.
        $this->filesystem->write(
            self::FOLDER . '/ITEM001.xml',
            $this->unsupportedInteractionItemXml('ITEM001'),
        );

        // The old content of the item being replaced is never parsed, so the
        // update repairs the unsupported item instead of refusing.
        $updated = $this->editor->updateItem('ITEM001', $this->itemXml('ITEM001', 'Hersteld'));

        $this->assertSame('ITEM001', $updated->identifier);
        $this->assertStringContainsString('Hersteld', $this->read('ITEM001.xml'));
    }

    #[Test]
    public function addItemValidatesBeforeTouchingTheFilesystem(): void
    {
        $this->seedEmptyDraft();

        $this->expectException(InvalidAssessmentItemException::class);

        $this->editor->addItem('<not-an-item xmlns="' . self::ASI_NAMESPACE . '"/>');
    }

    #[Test]
    public function addItemThrowsWhenManifestHasNoAssessmentTestResource(): void
    {
        $this->filesystem->write(
            self::FOLDER . '/imsmanifest.xml',
            '<manifest xmlns="' . self::MANIFEST_NAMESPACE . '" identifier="M"><resources/></manifest>',
        );

        $this->expectException(InvalidQtiPackageException::class);

        $this->editor->addItem($this->itemXml('X'));
    }

    #[Test]
    public function addItemThrowsWhenAssessmentTestHasNoSection(): void
    {
        $this->seedEmptyDraft();
        $this->filesystem->write(
            self::FOLDER . '/AssessmentTest.xml',
            '<qti-assessment-test xmlns="' . self::ASI_NAMESPACE . '" identifier="T" title=""/>',
        );

        $this->expectException(InvalidAssessmentTestException::class);

        $this->editor->addItem($this->itemXml('X'));
    }

    #[Test]
    public function addItemWritesNothingWhenTheAssessmentTestHasNoSection(): void
    {
        $this->seedEmptyDraft();
        $this->filesystem->write(
            self::FOLDER . '/AssessmentTest.xml',
            '<qti-assessment-test xmlns="' . self::ASI_NAMESPACE . '" identifier="T" title=""/>',
        );

        try {
            $this->editor->addItem($this->itemXml('X'));
            $this->fail('Expected InvalidAssessmentTestException');
        } catch (InvalidAssessmentTestException) {
            // A structural failure must abort before the package is stored.
            $this->assertFalse($this->filesystem->fileExists(self::FOLDER . '/ITEM001.xml'));
        }
    }

    #[Test]
    public function addItemRefusesAnItemWithAnUnsupportedInteraction(): void
    {
        $this->seedEmptyDraft();

        $this->expectException(UnsupportedQtiConstructException::class);

        $this->editor->addItem($this->unsupportedInteractionItemXml('X'));
    }

    #[Test]
    public function addItemRefusesAnItemWithATemplateDeclaration(): void
    {
        $this->seedEmptyDraft();

        $this->expectException(UnsupportedQtiConstructException::class);

        $this->editor->addItem(sprintf(
            '<qti-assessment-item xmlns="%s" identifier="X" title="Vraag" time-dependent="false">'
            . '<qti-template-declaration identifier="T1" cardinality="single" base-type="integer"/>'
            . '<qti-item-body><p>Vraag</p></qti-item-body></qti-assessment-item>',
            self::ASI_NAMESPACE,
        ));
    }

    #[Test]
    public function addItemRefusesAnUnknownResponseProcessingTemplate(): void
    {
        $this->seedEmptyDraft();

        $this->expectException(UnsupportedQtiConstructException::class);

        $this->editor->addItem(sprintf(
            '<qti-assessment-item xmlns="%s" identifier="X" title="Vraag" time-dependent="false">'
            . '<qti-item-body><p>Vraag</p></qti-item-body>'
            . '<qti-response-processing template="https://example.com/custom_template.xml"/>'
            . '</qti-assessment-item>',
            self::ASI_NAMESPACE,
        ));
    }

    #[Test]
    public function editingRefusesWhenAnExistingItemIsUnsupported(): void
    {
        $this->seedEmptyDraft();
        $this->editor->addItem($this->itemXml('X'));
        $this->filesystem->write(
            self::FOLDER . '/ITEM001.xml',
            $this->unsupportedInteractionItemXml('ITEM001'),
        );

        // Regenerating would re-serialize ITEM001 and lose the unsupported
        // interaction, so any other edit is refused.
        $this->expectException(UnsupportedQtiConstructException::class);

        $this->editor->addItem($this->itemXml('X'));
    }

    #[Test]
    public function editingRefusesWhenTheTestContainsOutcomeProcessing(): void
    {
        $this->seedEmptyDraft();
        $this->filesystem->write(
            self::FOLDER . '/AssessmentTest.xml',
            sprintf(
                '<qti-assessment-test xmlns="%s" identifier="T" title="">'
                . '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">'
                . '<qti-assessment-section identifier="s" title="" visible="true"/>'
                . '</qti-test-part>'
                . '<qti-outcome-processing><qti-set-outcome-value identifier="SCORE"/></qti-outcome-processing>'
                . '</qti-assessment-test>',
                self::ASI_NAMESPACE,
            ),
        );

        $this->expectException(UnsupportedQtiConstructException::class);

        $this->editor->addItem($this->itemXml('X'));
    }

    #[Test]
    public function editingRefusesNestedSections(): void
    {
        $this->seedEmptyDraft();
        $this->writeTest(
            '<qti-assessment-section identifier="outer" title="" visible="true">'
            . '<qti-assessment-item-ref identifier="ITEM_A" href="ITEM_A.xml"/>'
            . '<qti-assessment-section identifier="inner" title="" visible="true">'
            . '<qti-assessment-item-ref identifier="NESTED" href="NESTED.xml"/>'
            . '</qti-assessment-section>'
            . '</qti-assessment-section>',
        );

        // Nested sections are not representable in the typed model: regenerating
        // would silently delete them, so the package is refused.
        $this->expectException(UnsupportedQtiConstructException::class);

        $this->editor->reorderItems(['ITEM_A']);
    }

    #[Test]
    public function reorderItemsRewritesTheSectionInTheGivenOrder(): void
    {
        $this->seedDraftWithItems();

        $this->editor->reorderItems(['ITEM003', 'ITEM001', 'ITEM002']);

        $this->assertSame(['ITEM003', 'ITEM001', 'ITEM002'], $this->itemRefIdentifiers('AssessmentTest.xml'));
    }

    #[Test]
    public function reorderItemsKeepsTheAssessmentTestFileAtTheManifestHref(): void
    {
        $this->seedEmptyDraft(testHref: 'test/Main.xml');
        $this->editor->addItem($this->itemXml('X'));
        $this->editor->addItem($this->itemXml('X'));

        $this->editor->reorderItems(['ITEM002', 'ITEM001']);

        $this->assertSame(['ITEM002', 'ITEM001'], $this->itemRefIdentifiers('test/Main.xml'));
    }

    #[Test]
    public function reorderItemsThrowsWhenAnIdentifierIsUnknown(): void
    {
        $this->seedDraftWithItems();

        $this->expectException(InvalidItemOrderException::class);

        $this->editor->reorderItems(['ITEM001', 'ITEM002', 'ITEM999']);
    }

    #[Test]
    public function reorderItemsThrowsWhenAnItemIsMissingFromTheNewOrder(): void
    {
        $this->seedDraftWithItems();

        $this->expectException(InvalidItemOrderException::class);

        $this->editor->reorderItems(['ITEM001', 'ITEM002']);
    }

    #[Test]
    public function reorderItemsThrowsWhenAnIdentifierIsListedTwice(): void
    {
        $this->seedDraftWithItems();

        $this->expectException(InvalidItemOrderException::class);

        $this->editor->reorderItems(['ITEM001', 'ITEM002', 'ITEM002']);
    }

    #[Test]
    public function reorderItemsLeavesTheFileUnchangedWhenTheOrderIsInvalid(): void
    {
        $this->seedDraftWithItems();
        $before = $this->read('AssessmentTest.xml');

        try {
            $this->editor->reorderItems(['ITEM001', 'ITEM002', 'ITEM999']);
            $this->fail('Expected InvalidItemOrderException');
        } catch (InvalidItemOrderException) {
            $this->assertSame($before, $this->read('AssessmentTest.xml'));
        }
    }

    #[Test]
    public function reorderItemsThrowsWhenTheAssessmentTestHasNoSection(): void
    {
        $this->seedEmptyDraft();
        $this->filesystem->write(
            self::FOLDER . '/AssessmentTest.xml',
            '<qti-assessment-test xmlns="' . self::ASI_NAMESPACE . '" identifier="T" title=""/>',
        );

        $this->expectException(InvalidAssessmentTestException::class);

        $this->editor->reorderItems([]);
    }

    #[Test]
    public function reorderItemsThrowsWhenTheTestHasADuplicateItemRef(): void
    {
        $this->seedEmptyDraft();
        $this->writeTest(
            '<qti-assessment-section identifier="s" title="" visible="true">'
            . '<qti-assessment-item-ref identifier="ITEM_A" href="ITEM_A.xml"/>'
            . '<qti-assessment-item-ref identifier="ITEM_A" href="ITEM_A.xml"/>'
            . '</qti-assessment-section>',
        );

        $this->expectException(InvalidAssessmentTestException::class);

        $this->editor->reorderItems(['ITEM_A']);
    }

    #[Test]
    public function reorderItemsThrowsWhenTheTestHasAnItemRefWithoutIdentifier(): void
    {
        $this->seedEmptyDraft();
        $this->writeTest(
            '<qti-assessment-section identifier="s" title="" visible="true">'
            . '<qti-assessment-item-ref href="ITEM_A.xml"/>'
            . '</qti-assessment-section>',
        );

        $this->expectException(InvalidAssessmentTestException::class);

        $this->editor->reorderItems([]);
    }

    #[Test]
    public function updateItemCarriesOverMediaMetadataAndOtherItems(): void
    {
        $this->seedDraftWithMediaAndMetadata();

        $this->editor->updateItem('ITEM002', $this->itemXml('ITEM002', 'Bijgewerkte vraag'));

        // The edited item changed.
        $this->assertStringContainsString('Bijgewerkte vraag', $this->read('ITEM002.xml'));

        // The other item survived semantically: same identifier, title and image reference.
        $item1 = $this->read('ITEM001.xml');
        $this->assertStringContainsString('identifier="ITEM001"', $item1);
        $this->assertStringContainsString('title="Vraag met afbeelding"', $item1);
        $this->assertStringContainsString('resources/pic.png', $item1);

        // Media kept its path and bytes.
        $this->assertSame('PNGDATA123', $this->read('resources/pic.png'));

        // Metadata resource and its dependency from the test resource survived.
        $this->assertSame('<lom xmlns="http://ltsc.ieee.org/xsd/LOM"/>', trim(preg_replace('/^<\?xml[^>]*\?>\s*/', '', $this->read('metadata.xml')) ?? ''));
        $manifest = $this->read('imsmanifest.xml');
        $metadataResource = $this->findElement($manifest, self::MANIFEST_NAMESPACE, 'resource', 'META1');
        $this->assertInstanceOf(DOMElement::class, $metadataResource);
        $this->assertSame('resourcemetadata/xml', $metadataResource->getAttribute('type'));
        $testResource = $this->findElement($manifest, self::MANIFEST_NAMESPACE, 'resource', 'test');
        $this->assertInstanceOf(DOMElement::class, $testResource);
        $this->assertStringContainsString('identifierref="META1"', $this->elementXml($testResource));

        // The media file is still registered as a webcontent resource at its original path.
        $this->assertStringContainsString('href="resources/pic.png"', $manifest);

        // The item order is untouched.
        $this->assertSame(['ITEM001', 'ITEM002'], $this->itemRefIdentifiers('AssessmentTest.xml'));
    }

    private function seedDraftWithItems(): void
    {
        $this->seedEmptyDraft();
        $this->editor->addItem($this->itemXml('X'));
        $this->editor->addItem($this->itemXml('X'));
        $this->editor->addItem($this->itemXml('X'));
    }

    private function seedDraftWithMediaAndMetadata(): void
    {
        $manifest = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest xmlns="%s" identifier="MANIFEST-1"><organizations/><resources>'
            . '<resource identifier="test" type="imsqti_test_xmlv3p0" href="AssessmentTest.xml">'
            . '<file href="AssessmentTest.xml"/><dependency identifierref="META1"/></resource>'
            . '<resource identifier="ITEM001" type="imsqti_item_xmlv3p0" href="ITEM001.xml"><file href="ITEM001.xml"/></resource>'
            . '<resource identifier="ITEM002" type="imsqti_item_xmlv3p0" href="ITEM002.xml"><file href="ITEM002.xml"/></resource>'
            . '<resource identifier="RES1" type="webcontent" href="resources/pic.png"><file href="resources/pic.png"/></resource>'
            . '<resource identifier="META1" type="resourcemetadata/xml" href="metadata.xml"><file href="metadata.xml"/></resource>'
            . '</resources></manifest>',
            self::MANIFEST_NAMESPACE,
        );

        $test = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<qti-assessment-test xmlns="%s" identifier="test-1" title="">'
            . '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">'
            . '<qti-assessment-section identifier="s" title="" visible="true">'
            . '<qti-assessment-item-ref identifier="ITEM001" href="ITEM001.xml"/>'
            . '<qti-assessment-item-ref identifier="ITEM002" href="ITEM002.xml"/>'
            . '</qti-assessment-section></qti-test-part></qti-assessment-test>',
            self::ASI_NAMESPACE,
        );

        $item1 = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<qti-assessment-item xmlns="%s" identifier="ITEM001" title="Vraag met afbeelding" time-dependent="false">'
            . '<qti-item-body><p><img src="resources/pic.png" alt="Afbeelding"/></p></qti-item-body>'
            . '</qti-assessment-item>',
            self::ASI_NAMESPACE,
        );

        $this->filesystem->write(self::FOLDER . '/imsmanifest.xml', $manifest);
        $this->filesystem->write(self::FOLDER . '/AssessmentTest.xml', $test);
        $this->filesystem->write(self::FOLDER . '/ITEM001.xml', $item1);
        $this->filesystem->write(self::FOLDER . '/ITEM002.xml', $this->itemXml('ITEM002'));
        $this->filesystem->write(self::FOLDER . '/resources/pic.png', 'PNGDATA123');
        $this->filesystem->write(self::FOLDER . '/metadata.xml', '<lom xmlns="http://ltsc.ieee.org/xsd/LOM"/>');
    }

    /**
     * @return list<string>
     */
    private function itemRefIdentifiers(string $path): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($this->read($path));

        $identifiers = [];
        foreach ($dom->getElementsByTagNameNS(self::ASI_NAMESPACE, 'qti-assessment-item-ref') as $itemRef) {
            $identifiers[] = $itemRef->getAttribute('identifier');
        }

        return $identifiers;
    }

    private function writeTest(string $sectionsXml): void
    {
        $this->filesystem->write(
            self::FOLDER . '/AssessmentTest.xml',
            sprintf(
                '<qti-assessment-test xmlns="%s" identifier="T" title="">'
                . '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">'
                . '%s'
                . '</qti-test-part></qti-assessment-test>',
                self::ASI_NAMESPACE,
                $sectionsXml,
            ),
        );
    }

    private function seedEmptyDraft(string $testHref = 'AssessmentTest.xml'): void
    {
        $manifest = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest xmlns="%s" identifier="MANIFEST-1"><organizations/><resources>'
            . '<resource identifier="test" type="imsqti_test_xmlv3p0" href="%s"><file href="%s"/></resource>'
            . '</resources></manifest>',
            self::MANIFEST_NAMESPACE,
            $testHref,
            $testHref,
        );

        $test = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<qti-assessment-test xmlns="%s" identifier="test-1" title="">'
            . '<qti-test-part identifier="testPart-1" navigation-mode="linear" submission-mode="individual">'
            . '<qti-assessment-section identifier="section-1" title="" visible="true"/>'
            . '</qti-test-part></qti-assessment-test>',
            self::ASI_NAMESPACE,
        );

        $this->filesystem->write(self::FOLDER . '/imsmanifest.xml', $manifest);
        $this->filesystem->write(self::FOLDER . '/' . $testHref, $test);
    }

    private function itemXml(
        string $identifier,
        string $title = 'Vraag',
        string $timeDependent = 'false',
        ?string $language = null,
    ): string {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<qti-assessment-item xmlns="%s" identifier="%s" title="%s" time-dependent="%s"%s>'
            . '<qti-item-body><p>Vraag tekst</p></qti-item-body></qti-assessment-item>',
            self::ASI_NAMESPACE,
            $identifier,
            $title,
            $timeDependent,
            $language !== null ? sprintf(' xml:lang="%s"', $language) : '',
        );
    }

    private function unsupportedInteractionItemXml(string $identifier): string
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<qti-assessment-item xmlns="%s" identifier="%s" title="Vraag" time-dependent="false">'
            . '<qti-item-body><qti-slider-interaction response-identifier="RESPONSE" lower-bound="0" upper-bound="10"/></qti-item-body>'
            . '</qti-assessment-item>',
            self::ASI_NAMESPACE,
            $identifier,
        );
    }

    private function read(string $path): string
    {
        return $this->filesystem->read(self::FOLDER . '/' . $path);
    }

    private function findElement(string $xml, string $namespace, string $localName, string $identifier): ?DOMElement
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        foreach ($dom->getElementsByTagNameNS($namespace, $localName) as $element) {
            if ($element->getAttribute('identifier') === $identifier) {
                return $element;
            }
        }

        return null;
    }

    private function elementXml(DOMElement $element): string
    {
        return (string) $element->ownerDocument?->saveXML($element);
    }
}
