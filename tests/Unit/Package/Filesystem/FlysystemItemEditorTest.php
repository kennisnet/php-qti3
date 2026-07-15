<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Filesystem;

use DOMDocument;
use DOMElement;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Exception\InvalidAssessmentItemException;
use Qti3\Package\Exception\InvalidItemOrderException;
use Qti3\Package\Exception\InvalidQtiPackageException;
use Qti3\Package\Filesystem\FlysystemItemEditor;
use Qti3\Package\Service\ItemIdentifierGenerator;
use Qti3\Package\Validator\AssessmentItemValidator;
use Qti3\Shared\Exception\ResourceNotFoundException;
use Qti3\Shared\Xml\Reader\XmlReader;

final class FlysystemItemEditorTest extends TestCase
{
    private const string FOLDER = 'qti/v1';
    private const string ASI_NAMESPACE = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';
    private const string MANIFEST_NAMESPACE = 'http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1';

    private FilesystemOperator $filesystem;
    private FlysystemItemEditor $editor;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());

        $xmlReader = new XmlReader();
        $this->editor = new FlysystemItemEditor(
            self::FOLDER,
            $this->filesystem,
            $xmlReader,
            new AssessmentItemValidator($xmlReader),
            new ItemIdentifierGenerator(),
        );
    }

    #[Test]
    public function addItemAssignsTheNextIdentifierAndWritesTheItemFile(): void
    {
        $this->seedEmptyDraft();

        $editedItem = $this->editor->addItem($this->itemXml('PLACEHOLDER'));

        $this->assertSame('ITEM001', $editedItem->identifier);
        $stored = $this->read('ITEM001.xml');
        $this->assertStringContainsString('identifier="ITEM001"', $stored);
        $this->assertStringNotContainsString('PLACEHOLDER', $stored);
        $this->assertSame($stored, $editedItem->xml);
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
    public function addItemRegistersTheResourceInsideManifestResources(): void
    {
        $this->seedEmptyDraft();

        $this->editor->addItem($this->itemXml('X'));

        $resource = $this->findElement($this->read('imsmanifest.xml'), self::MANIFEST_NAMESPACE, 'resource', 'ITEM001');
        $this->assertInstanceOf(DOMElement::class, $resource);
        $this->assertSame('imsqti_item_xmlv3p0', $resource->getAttribute('type'));
        $this->assertSame('ITEM001.xml', $resource->getAttribute('href'));
        $this->assertSame('resources', $resource->parentNode?->localName);

        $file = $resource->getElementsByTagNameNS(self::MANIFEST_NAMESPACE, 'file')->item(0);
        $this->assertInstanceOf(DOMElement::class, $file);
        $this->assertSame('ITEM001.xml', $file->getAttribute('href'));
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
    public function addItemDiscoversTheAssessmentTestFileFromTheManifestHref(): void
    {
        $this->seedEmptyDraft(testHref: 'test/Main.xml');

        $this->editor->addItem($this->itemXml('X'));

        $this->assertStringContainsString('qti-assessment-item-ref', $this->read('test/Main.xml'));
    }

    #[Test]
    public function updateItemOverwritesOnlyTheItemFile(): void
    {
        $this->seedEmptyDraft();
        $this->editor->addItem($this->itemXml('X'));
        $manifestBefore = $this->read('imsmanifest.xml');
        $testBefore = $this->read('AssessmentTest.xml');

        $this->editor->updateItem('ITEM001', $this->itemXml('ITEM001', 'Bijgewerkte vraag'));

        $this->assertStringContainsString('Bijgewerkte vraag', $this->read('ITEM001.xml'));
        $this->assertSame($manifestBefore, $this->read('imsmanifest.xml'));
        $this->assertSame($testBefore, $this->read('AssessmentTest.xml'));
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
    public function addItemValidatesBeforeTouchingTheFilesystem(): void
    {
        $this->seedEmptyDraft();

        $this->expectException(InvalidAssessmentItemException::class);

        $this->editor->addItem('<not-an-item xmlns="' . self::ASI_NAMESPACE . '"/>');
    }

    #[Test]
    public function addItemThrowsWhenManifestHasNoResourcesElement(): void
    {
        $this->filesystem->write(
            self::FOLDER . '/imsmanifest.xml',
            '<manifest xmlns="' . self::MANIFEST_NAMESPACE . '" identifier="M"/>',
        );

        $this->expectException(InvalidQtiPackageException::class);

        $this->editor->addItem($this->itemXml('X'));
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

        $this->expectException(InvalidQtiPackageException::class);

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
            $this->fail('Expected InvalidQtiPackageException');
        } catch (InvalidQtiPackageException) {
            // A structural failure must abort before any file is written.
            $this->assertFalse($this->filesystem->fileExists(self::FOLDER . '/ITEM001.xml'));
        }
    }

    #[Test]
    public function reorderItemsRewritesTheSectionInTheGivenOrder(): void
    {
        $this->seedDraftWithItems();

        $this->editor->reorderItems(['ITEM003', 'ITEM001', 'ITEM002']);

        $this->assertSame(['ITEM003', 'ITEM001', 'ITEM002'], $this->itemRefIdentifiers('AssessmentTest.xml'));
    }

    #[Test]
    public function reorderItemsDiscoversTheAssessmentTestFileFromTheManifestHref(): void
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

        $this->expectException(InvalidQtiPackageException::class);

        $this->editor->reorderItems([]);
    }

    #[Test]
    public function reorderItemsOnlyTouchesDirectChildrenAndLeavesNestedSectionsIntact(): void
    {
        $this->seedEmptyDraft();
        $this->writeTest(
            '<qti-assessment-section identifier="outer" title="" visible="true">'
            . '<qti-assessment-item-ref identifier="ITEM_A" href="ITEM_A.xml"/>'
            . '<qti-assessment-section identifier="inner" title="" visible="true">'
            . '<qti-assessment-item-ref identifier="NESTED" href="NESTED.xml"/>'
            . '</qti-assessment-section>'
            . '<qti-assessment-item-ref identifier="ITEM_B" href="ITEM_B.xml"/>'
            . '</qti-assessment-section>',
        );

        // Only ITEM_A and ITEM_B (direct children) are ours to reorder; NESTED belongs to
        // the child section and must be left where it is.
        $this->editor->reorderItems(['ITEM_B', 'ITEM_A']);

        $this->assertSame(
            ['ITEM_B', 'ITEM_A'],
            $this->directChildItemRefIdentifiers('AssessmentTest.xml', 'outer'),
        );
        $this->assertContains('NESTED', $this->itemRefIdentifiers('AssessmentTest.xml'));
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

        $this->expectException(InvalidQtiPackageException::class);

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

        $this->expectException(InvalidQtiPackageException::class);

        $this->editor->reorderItems([]);
    }

    private function seedDraftWithItems(): void
    {
        $this->seedEmptyDraft();
        $this->editor->addItem($this->itemXml('X'));
        $this->editor->addItem($this->itemXml('X'));
        $this->editor->addItem($this->itemXml('X'));
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

    /**
     * @return list<string>
     */
    private function directChildItemRefIdentifiers(string $path, string $sectionIdentifier): array
    {
        $section = $this->findElement($this->read($path), self::ASI_NAMESPACE, 'qti-assessment-section', $sectionIdentifier);

        $identifiers = [];
        foreach ($section?->childNodes ?? [] as $childNode) {
            if (
                $childNode instanceof DOMElement
                && $childNode->namespaceURI === self::ASI_NAMESPACE
                && $childNode->localName === 'qti-assessment-item-ref'
            ) {
                $identifiers[] = $childNode->getAttribute('identifier');
            }
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

    private function itemXml(string $identifier, string $title = 'Vraag'): string
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<qti-assessment-item xmlns="%s" identifier="%s" title="%s" time-dependent="false">'
            . '<qti-item-body/></qti-assessment-item>',
            self::ASI_NAMESPACE,
            $identifier,
            $title,
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
}
