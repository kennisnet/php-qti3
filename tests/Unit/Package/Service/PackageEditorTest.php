<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Service;

use DOMDocument;
use DOMElement;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\AssessmentTest\Exception\InvalidAssessmentTestException;
use Qti3\AssessmentTest\Exception\InvalidItemOrderException;
use Qti3\Package\Filesystem\FlysystemPackageFactory;
use Qti3\Package\Model\PackageFile\XmlFile;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Service\PackageEditor;
use Qti3\Package\Downloader\Resource\IResourceDownloader;
use Qti3\Package\Validator\Resource\IResourceValidator;
use Qti3\QtiClient;
use Qti3\Shared\Exception\ResourceNotFoundException;

final class PackageEditorTest extends TestCase
{
    private const string FOLDER = 'qti/v1';
    private const string ASI_NAMESPACE = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';
    private const string MANIFEST_NAMESPACE = 'http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1';
    private const string TEST_ID = 'test';

    private FilesystemOperator $filesystem;
    private QtiClient $client;
    private PackageEditor $editor;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());

        $this->client = new QtiClient(
            new FlysystemPackageFactory($this->filesystem),
            $this->createStub(IResourceValidator::class),
            $this->createStub(IResourceDownloader::class),
        );
        $this->editor = $this->client->getPackageEditor();
    }

    #[Test]
    public function getAvailableItemIdentifierVendsSequentialIdentifiers(): void
    {
        $package = $this->emptyDraft();

        $this->assertSame('ITEM001', $this->editor->getAvailableItemIdentifier($package));

        $this->addItem($package);
        $this->assertSame('ITEM002', $this->editor->getAvailableItemIdentifier($package));
    }

    #[Test]
    public function addItemUsesTheItemsOwnIdentifierAndReturnsTheResource(): void
    {
        $package = $this->emptyDraft();

        $item = $this->editor->addItemToTest($package, self::TEST_ID, $this->item('ITEM001', 'Mijn vraag'));

        $this->assertSame('ITEM001', $item->identifier);
        $stored = (string) $package->getFile('ITEM001.xml');
        $this->assertStringContainsString('identifier="ITEM001"', $stored);
        $this->assertStringContainsString('Mijn vraag', $stored);
        $this->assertSame($stored, (string) $item->getMainFile());
    }

    #[Test]
    public function addItemFromAnXmlStringParsesItAndAddsItToTheTest(): void
    {
        // The documented use case: an item arrives as a raw QTI 3 XML string
        // (e.g. from a UI or API). The caller obtains a free identifier, builds
        // the XML with it, parses it via parseFromString() and hands the model
        // to the editor.
        $package = $this->emptyDraft();

        $identifier = $this->editor->getAvailableItemIdentifier($package);
        $item = $this->client->getAssessmentItemParser()->parseFromString(
            $this->itemXml($identifier, 'Vraag uit XML'),
        );

        $added = $this->editor->addItemToTest($package, self::TEST_ID, $item);

        $this->assertSame('ITEM001', $added->identifier);
        $this->assertSame(['ITEM001'], $this->itemRefIdentifiers($package));
        $stored = (string) $package->getFile('ITEM001.xml');
        $this->assertStringContainsString('identifier="ITEM001"', $stored);
        $this->assertStringContainsString('Vraag uit XML', $stored);
    }

    #[Test]
    public function addItemAppendsAnItemRefToTheSectionByDefault(): void
    {
        $package = $this->emptyDraft();
        $this->addItem($package);

        $this->addItem($package);

        $this->assertSame(['ITEM001', 'ITEM002'], $this->itemRefIdentifiers($package));
    }

    #[Test]
    public function addItemInsertsAtTheGivenPosition(): void
    {
        $package = $this->emptyDraft();
        $this->addItem($package);
        $this->addItem($package);

        $this->editor->addItemToTest($package, self::TEST_ID, $this->item('ITEM003'), position: 1);

        $this->assertSame(['ITEM001', 'ITEM003', 'ITEM002'], $this->itemRefIdentifiers($package));
    }

    #[Test]
    public function addItemRegistersTheResourceAndTestDependencyInTheManifest(): void
    {
        $package = $this->emptyDraft();

        $this->addItem($package);

        $manifest = (string) $package->manifest;
        $resource = $this->findElement($manifest, self::MANIFEST_NAMESPACE, 'resource', 'ITEM001');
        $this->assertInstanceOf(DOMElement::class, $resource);
        $this->assertSame('imsqti_item_xmlv3p0', $resource->getAttribute('type'));
        $this->assertSame('ITEM001.xml', $resource->getAttribute('href'));

        $testResource = $this->findElement($manifest, self::MANIFEST_NAMESPACE, 'resource', self::TEST_ID);
        $this->assertInstanceOf(DOMElement::class, $testResource);
        $this->assertStringContainsString('identifierref="ITEM001"', $this->elementXml($testResource));
    }

    #[Test]
    public function addItemKeepsTheAssessmentTestFileAtItsHref(): void
    {
        $package = $this->emptyDraft(testHref: 'test/Main.xml');

        $this->addItem($package);

        $this->assertStringContainsString('qti-assessment-item-ref', (string) $package->getFile('test/Main.xml'));
    }

    #[Test]
    public function addItemThrowsWhenTheIdentifierAlreadyExists(): void
    {
        $package = $this->emptyDraft();
        $this->editor->addItemToTest($package, self::TEST_ID, $this->item('ITEM001'));

        try {
            $this->editor->addItemToTest($package, self::TEST_ID, $this->item('ITEM001'));
            $this->fail('Expected InvalidAssessmentTestException');
        } catch (InvalidAssessmentTestException) {
            // The section still has just one ref: nothing was added twice.
            $this->assertSame(['ITEM001'], $this->itemRefIdentifiers($package));
        }
    }

    #[Test]
    public function addItemThrowsWhenTheTestDoesNotExist(): void
    {
        $package = $this->emptyDraft();

        $this->expectException(ResourceNotFoundException::class);

        $this->editor->addItemToTest($package, 'does-not-exist', $this->item('ITEM001'));
    }

    #[Test]
    public function addItemThrowsAndLeavesThePackageUntouchedWhenTheTestHasNoSection(): void
    {
        $package = $this->draftWithoutSection();

        try {
            $this->editor->addItemToTest($package, self::TEST_ID, $this->item('ITEM001'));
            $this->fail('Expected InvalidAssessmentTestException');
        } catch (InvalidAssessmentTestException) {
            $this->assertFalse($package->hasFile('ITEM001.xml'));
        }
    }

    #[Test]
    public function addItemSucceedsEvenWhenAnUnrelatedItemUsesAnUnsupportedConstruct(): void
    {
        // Surgical editing never re-serializes untouched items, so an
        // unsupported interaction in another item does not block the edit.
        $package = $this->draftWithUnsupportedItem();

        $item = $this->addItem($package);

        $this->assertSame('ITEM002', $item->identifier);
        $this->assertSame(['ITEM001', 'ITEM002'], $this->itemRefIdentifiers($package));
    }

    #[Test]
    public function updateItemReplacesTheContentAndLeavesManifestAndTestEquivalent(): void
    {
        $package = $this->emptyDraft();
        $this->addItem($package);
        $manifestBefore = (string) $package->manifest;
        $testBefore = (string) $package->getFile('AssessmentTest.xml');

        $this->editor->updateItem($package, $this->item('ITEM001', 'Bijgewerkte vraag'));

        $this->assertStringContainsString('Bijgewerkte vraag', (string) $package->getFile('ITEM001.xml'));
        $this->assertXmlStringEqualsXmlString($manifestBefore, (string) $package->manifest);
        $this->assertXmlStringEqualsXmlString($testBefore, (string) $package->getFile('AssessmentTest.xml'));
    }

    #[Test]
    public function updateItemThrowsWhenTheItemDoesNotExist(): void
    {
        $package = $this->emptyDraft();

        $this->expectException(ResourceNotFoundException::class);

        $this->editor->updateItem($package, $this->item('ITEM999'));
    }

    #[Test]
    public function updateItemCanRepairAnItemWithUnsupportedContent(): void
    {
        $package = $this->emptyDraft();
        $this->addItem($package);
        // Corrupt ITEM001 with an unsupported interaction; its old content is
        // never parsed, so the update repairs it.
        $this->overwriteItemFile($package, 'ITEM001', $this->unsupportedInteractionItemXml('ITEM001'));

        $updated = $this->editor->updateItem($package, $this->item('ITEM001', 'Hersteld'));

        $this->assertSame('ITEM001', $updated->identifier);
        $this->assertStringContainsString('Hersteld', (string) $package->getFile('ITEM001.xml'));
    }

    #[Test]
    public function reorderItemsRewritesTheSectionInTheGivenOrder(): void
    {
        $package = $this->draftWithItems(3);

        $this->editor->reorderItemsInTest($package, self::TEST_ID, ['ITEM003', 'ITEM001', 'ITEM002']);

        $this->assertSame(['ITEM003', 'ITEM001', 'ITEM002'], $this->itemRefIdentifiers($package));
    }

    #[Test]
    public function reorderItemsThrowsWhenAnIdentifierIsUnknown(): void
    {
        $package = $this->draftWithItems(3);

        $this->expectException(InvalidItemOrderException::class);

        $this->editor->reorderItemsInTest($package, self::TEST_ID, ['ITEM001', 'ITEM002', 'ITEM999']);
    }

    #[Test]
    public function reorderItemsThrowsWhenAnItemIsMissingFromTheNewOrder(): void
    {
        $package = $this->draftWithItems(3);

        $this->expectException(InvalidItemOrderException::class);

        $this->editor->reorderItemsInTest($package, self::TEST_ID, ['ITEM001', 'ITEM002']);
    }

    #[Test]
    public function reorderItemsLeavesTheFileUnchangedWhenTheOrderIsInvalid(): void
    {
        $package = $this->draftWithItems(3);
        $before = (string) $package->getFile('AssessmentTest.xml');

        try {
            $this->editor->reorderItemsInTest($package, self::TEST_ID, ['ITEM001', 'ITEM002', 'ITEM999']);
            $this->fail('Expected InvalidItemOrderException');
        } catch (InvalidItemOrderException) {
            $this->assertSame($before, (string) $package->getFile('AssessmentTest.xml'));
        }
    }

    #[Test]
    public function editsTargetOnlyTheGivenTestInAMultiTestPackage(): void
    {
        $package = $this->twoTestDraft();

        $this->editor->addItemToTest($package, 'testB', $this->item('ITEM001'));

        // The item ref landed in test B; test A is untouched.
        $this->assertSame(['ITEM_A'], $this->itemRefIdentifiers($package, 'TestA.xml'));
        $this->assertSame(['ITEM_B', 'ITEM001'], $this->itemRefIdentifiers($package, 'TestB.xml'));
        $this->assertTrue($package->hasFile('ITEM001.xml'));
    }

    #[Test]
    public function addItemWithNewExternalMediaRegistersAWebcontentResource(): void
    {
        $package = $this->emptyDraft();

        $item = $this->editor->addItemToTest(
            $package,
            self::TEST_ID,
            $this->item('ITEM001', imageSrc: 'https://example.com/pic.png'),
        );

        $itemResourceXml = $this->elementXml(
            $this->findElement((string) $package->manifest, self::MANIFEST_NAMESPACE, 'resource', $item->identifier)
                ?? self::fail('Item resource missing'),
        );
        $this->assertStringContainsString('identifierref="RESOURCE001"', $itemResourceXml);

        $webcontent = $this->findElement((string) $package->manifest, self::MANIFEST_NAMESPACE, 'resource', 'RESOURCE001');
        $this->assertInstanceOf(DOMElement::class, $webcontent);
        $this->assertSame('webcontent', $webcontent->getAttribute('type'));
    }

    #[Test]
    public function updateItemKeepsMediaAlreadyInThePackageWithoutDuplicatingIt(): void
    {
        $package = $this->draftWithMedia();

        $this->editor->updateItem($package, $this->item('ITEM001', imageSrc: 'resources/pic.png'));

        $this->assertSame('PNGDATA123', $package->getFile('resources/pic.png')->getContent()->getContent());
        $this->assertSame(1, $this->countResourcesWithHref($package, 'resources/pic.png'));
    }

    // --- editor convenience --------------------------------------------------

    private function addItem(QtiPackage $package, string $testId = self::TEST_ID, int $position = -1): Resource
    {
        $identifier = $this->editor->getAvailableItemIdentifier($package);

        return $this->editor->addItemToTest($package, $testId, $this->item($identifier), $position);
    }

    private function item(string $identifier, string $title = 'Vraag', ?string $imageSrc = null): AssessmentItem
    {
        $xml = $imageSrc === null ? $this->itemXml($identifier, $title) : $this->itemXmlWithImage($identifier, $imageSrc);

        $element = $this->client->getXmlReader()->read($xml)->documentElement;
        self::assertInstanceOf(DOMElement::class, $element);
        $element->setAttribute('identifier', $identifier);

        return $this->client->getAssessmentItemParser()->parse($element);
    }

    // --- seeding helpers -----------------------------------------------------

    private function emptyDraft(string $testHref = 'AssessmentTest.xml'): QtiPackage
    {
        $manifest = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest xmlns="%s" identifier="MANIFEST-1"><organizations/><resources>'
            . '<resource identifier="%s" type="imsqti_test_xmlv3p0" href="%s"><file href="%s"/></resource>'
            . '</resources></manifest>',
            self::MANIFEST_NAMESPACE,
            self::TEST_ID,
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

        return $this->readPackage();
    }

    private function draftWithItems(int $count): QtiPackage
    {
        $package = $this->emptyDraft();
        for ($i = 0; $i < $count; $i++) {
            $this->addItem($package);
        }

        return $package;
    }

    private function draftWithoutSection(): QtiPackage
    {
        $manifest = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest xmlns="%s" identifier="MANIFEST-1"><organizations/><resources>'
            . '<resource identifier="%s" type="imsqti_test_xmlv3p0" href="AssessmentTest.xml"><file href="AssessmentTest.xml"/></resource>'
            . '</resources></manifest>',
            self::MANIFEST_NAMESPACE,
            self::TEST_ID,
        );
        $test = '<?xml version="1.0" encoding="UTF-8"?><qti-assessment-test xmlns="' . self::ASI_NAMESPACE . '" identifier="T" title=""/>';

        $this->filesystem->write(self::FOLDER . '/imsmanifest.xml', $manifest);
        $this->filesystem->write(self::FOLDER . '/AssessmentTest.xml', $test);

        return $this->readPackage();
    }

    private function twoTestDraft(): QtiPackage
    {
        $manifest = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest xmlns="%s" identifier="MANIFEST-1"><organizations/><resources>'
            . '<resource identifier="testA" type="imsqti_test_xmlv3p0" href="TestA.xml"><file href="TestA.xml"/></resource>'
            . '<resource identifier="testB" type="imsqti_test_xmlv3p0" href="TestB.xml"><file href="TestB.xml"/></resource>'
            . '<resource identifier="ITEM_A" type="imsqti_item_xmlv3p0" href="ITEM_A.xml"><file href="ITEM_A.xml"/></resource>'
            . '<resource identifier="ITEM_B" type="imsqti_item_xmlv3p0" href="ITEM_B.xml"><file href="ITEM_B.xml"/></resource>'
            . '</resources></manifest>',
            self::MANIFEST_NAMESPACE,
        );

        $this->filesystem->write(self::FOLDER . '/imsmanifest.xml', $manifest);
        $this->filesystem->write(self::FOLDER . '/TestA.xml', $this->testXml('testA-1', 'ITEM_A'));
        $this->filesystem->write(self::FOLDER . '/TestB.xml', $this->testXml('testB-1', 'ITEM_B'));
        $this->filesystem->write(self::FOLDER . '/ITEM_A.xml', $this->itemXml('ITEM_A'));
        $this->filesystem->write(self::FOLDER . '/ITEM_B.xml', $this->itemXml('ITEM_B'));

        return $this->readPackage();
    }

    private function draftWithMedia(): QtiPackage
    {
        $manifest = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest xmlns="%s" identifier="MANIFEST-1"><organizations/><resources>'
            . '<resource identifier="%s" type="imsqti_test_xmlv3p0" href="AssessmentTest.xml"><file href="AssessmentTest.xml"/><dependency identifierref="ITEM001"/></resource>'
            . '<resource identifier="ITEM001" type="imsqti_item_xmlv3p0" href="ITEM001.xml"><file href="ITEM001.xml"/><dependency identifierref="RES1"/></resource>'
            . '<resource identifier="RES1" type="webcontent" href="resources/pic.png"><file href="resources/pic.png"/></resource>'
            . '</resources></manifest>',
            self::MANIFEST_NAMESPACE,
            self::TEST_ID,
        );

        $this->filesystem->write(self::FOLDER . '/imsmanifest.xml', $manifest);
        $this->filesystem->write(self::FOLDER . '/AssessmentTest.xml', $this->testXml('test-1', 'ITEM001'));
        $this->filesystem->write(self::FOLDER . '/ITEM001.xml', $this->itemXmlWithImage('ITEM001', 'resources/pic.png'));
        $this->filesystem->write(self::FOLDER . '/resources/pic.png', 'PNGDATA123');

        return $this->readPackage();
    }

    private function draftWithUnsupportedItem(): QtiPackage
    {
        $manifest = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest xmlns="%s" identifier="MANIFEST-1"><organizations/><resources>'
            . '<resource identifier="%s" type="imsqti_test_xmlv3p0" href="AssessmentTest.xml"><file href="AssessmentTest.xml"/><dependency identifierref="ITEM001"/></resource>'
            . '<resource identifier="ITEM001" type="imsqti_item_xmlv3p0" href="ITEM001.xml"><file href="ITEM001.xml"/></resource>'
            . '</resources></manifest>',
            self::MANIFEST_NAMESPACE,
            self::TEST_ID,
        );

        $this->filesystem->write(self::FOLDER . '/imsmanifest.xml', $manifest);
        $this->filesystem->write(self::FOLDER . '/AssessmentTest.xml', $this->testXml('test-1', 'ITEM001'));
        $this->filesystem->write(self::FOLDER . '/ITEM001.xml', $this->unsupportedInteractionItemXml('ITEM001'));

        return $this->readPackage();
    }

    private function overwriteItemFile(QtiPackage $package, string $identifier, string $xml): void
    {
        $file = $package->getFile($identifier . '.xml');
        if ($file instanceof XmlFile) {
            $file->replaceContent($xml);
        }
    }

    private function readPackage(): QtiPackage
    {
        return $this->client->getQtiPackageReader()->fromFilesystem(self::FOLDER);
    }

    // --- xml builders --------------------------------------------------------

    private function testXml(string $identifier, string ...$itemIdentifiers): string
    {
        $itemRefs = '';
        foreach ($itemIdentifiers as $itemIdentifier) {
            $itemRefs .= sprintf('<qti-assessment-item-ref identifier="%s" href="%s.xml"/>', $itemIdentifier, $itemIdentifier);
        }

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<qti-assessment-test xmlns="%s" identifier="%s" title="">'
            . '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">'
            . '<qti-assessment-section identifier="s" title="" visible="true">%s</qti-assessment-section>'
            . '</qti-test-part></qti-assessment-test>',
            self::ASI_NAMESPACE,
            $identifier,
            $itemRefs,
        );
    }

    private function itemXml(string $identifier, string $title = 'Vraag'): string
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<qti-assessment-item xmlns="%s" identifier="%s" title="%s" time-dependent="false">'
            . '<qti-item-body><p>Vraag tekst</p></qti-item-body></qti-assessment-item>',
            self::ASI_NAMESPACE,
            $identifier,
            $title,
        );
    }

    private function itemXmlWithImage(string $identifier, string $src): string
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<qti-assessment-item xmlns="%s" identifier="%s" title="Vraag" time-dependent="false">'
            . '<qti-item-body><p><img src="%s" alt="Afbeelding"/></p></qti-item-body></qti-assessment-item>',
            self::ASI_NAMESPACE,
            $identifier,
            $src,
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

    // --- assertion helpers ---------------------------------------------------

    /**
     * @return list<string>
     */
    private function itemRefIdentifiers(QtiPackage $package, string $path = 'AssessmentTest.xml'): array
    {
        $dom = new DOMDocument();
        $dom->loadXML((string) $package->getFile($path));

        $identifiers = [];
        foreach ($dom->getElementsByTagNameNS(self::ASI_NAMESPACE, 'qti-assessment-item-ref') as $itemRef) {
            $identifiers[] = $itemRef->getAttribute('identifier');
        }

        return $identifiers;
    }

    private function countResourcesWithHref(QtiPackage $package, string $href): int
    {
        $dom = new DOMDocument();
        $dom->loadXML((string) $package->manifest);

        $count = 0;
        foreach ($dom->getElementsByTagNameNS(self::MANIFEST_NAMESPACE, 'resource') as $resource) {
            if ($resource->getAttribute('href') === $href) {
                $count++;
            }
        }

        return $count;
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
