<?php

declare(strict_types=1);

namespace Qti3\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
class PackageEditorIntegrationTest extends TestCase
{
    use QtiClientTestCaseTrait;

    private const string ASI_NAMESPACE = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';
    private const string MANIFEST_NAMESPACE = 'http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1';
    private const string PACKAGE_DIR = 'package';
    private const string TEST_ID = 'test';

    protected function setUp(): void
    {
        $this->setUpQtiClientTestCase();
    }

    protected function tearDown(): void
    {
        $this->tearDownQtiClientTestCase();
    }

    #[Test]
    public function addReorderAndRemoveSurviveAWriteAndReloadRoundTrip(): void
    {
        $this->seedPackageOnDisk();
        $client = $this->createClient();
        $editor = $client->getPackageEditor();

        // Load from disk.
        $package = $client->getQtiPackageReader()->fromFilesystem(self::PACKAGE_DIR);

        // Edit: add a third item, reorder, then remove the first.
        $added = $editor->addItemToTest(
            $package,
            self::TEST_ID,
            $client->getAssessmentItemParser()->parseFromString($this->itemXml('new', 'Nieuwe vraag'))->item,
        )->resource;
        $this->assertSame('ITEM003', $added->identifier);

        $editor->reorderItemsInTest($package, self::TEST_ID, ['ITEM003', 'ITEM001', 'ITEM002']);
        $editor->removeItemFromTest($package, self::TEST_ID, 'ITEM001');

        // Save back to disk.
        $client->getFilesystemPackageFactory()->getWriter(self::PACKAGE_DIR)->write($package);

        // Reload a fresh package and verify the edits persisted.
        $reloaded = $client->getQtiPackageReader()->fromFilesystem(self::PACKAGE_DIR);
        $test = $client->getTestBuilder()->buildFromPackage($reloaded, self::TEST_ID)->test;

        $this->assertSame(['ITEM003', 'ITEM002'], $test->getItemIdentifiers());
        $this->assertStringContainsString('Nieuwe vraag', (string) $reloaded->getFile('ITEM003.xml'));
        $this->assertTrue($reloaded->hasResource('ITEM003'));
        $this->assertFalse($reloaded->hasResource('ITEM001'));
        $this->assertFalse($reloaded->hasFile('ITEM001.xml'));
    }

    #[Test]
    public function inPlaceWriteSkippingUnmodifiedFilesStillProducesACompletePackage(): void
    {
        $this->seedPackageOnDisk();
        $client = $this->createClient();
        $editor = $client->getPackageEditor();

        $package = $client->getQtiPackageReader()->fromFilesystem(self::PACKAGE_DIR);

        // Only touch ITEM002; ITEM001 and its file stay untouched (unmodified).
        $editor->updateItem(
            $package,
            $client->getAssessmentItemParser()->parseFromString($this->itemXml('ITEM002', 'Aangepast'))->item,
        );

        // Write back over the source location, skipping unchanged files.
        $client->getFilesystemPackageFactory()->getWriter(self::PACKAGE_DIR)->write($package, skipUnmodifiedFiles: true);

        // Reload: the edit persisted and the skipped (untouched) item is intact.
        $reloaded = $client->getQtiPackageReader()->fromFilesystem(self::PACKAGE_DIR);
        $this->assertStringContainsString('Aangepast', (string) $reloaded->getFile('ITEM002.xml'));
        $this->assertStringContainsString('Vraag tekst', (string) $reloaded->getFile('ITEM001.xml'));
        $this->assertTrue($reloaded->hasResource('ITEM001'));
        $this->assertTrue($reloaded->hasResource('ITEM002'));
        $test = $client->getTestBuilder()->buildFromPackage($reloaded, self::TEST_ID)->test;
        $this->assertSame(['ITEM001', 'ITEM002'], $test->getItemIdentifiers());
    }

    #[Test]
    public function anAddedImageResourceReferencedFromAnItemSurvivesAWriteAndReload(): void
    {
        // The editor use case: upload an image into the package, reference it
        // from an item, then persist. updateItem wires the manifest dependency
        // on the in-package file automatically.
        $this->seedPackageOnDisk();
        $client = $this->createClient();
        $editor = $client->getPackageEditor();

        $package = $client->getQtiPackageReader()->fromFilesystem(self::PACKAGE_DIR);

        $pngBytes = "\x89PNG\r\n\x1a\nfake-image-bytes";
        $resource = $editor->addResource($package, 'resources/logo.png', $pngBytes)->resource;
        $this->assertSame('RESOURCE001', $resource->identifier);

        $editor->updateItem(
            $package,
            $client->getAssessmentItemParser()->parseFromString(
                $this->itemXmlWithImage('ITEM001', 'resources/logo.png'),
            )->item,
        );

        // Write back over the source, skipping unchanged files: the freshly
        // added image must still be written (it is modified by default).
        $client->getFilesystemPackageFactory()->getWriter(self::PACKAGE_DIR)->write($package, skipUnmodifiedFiles: true);

        $reloaded = $client->getQtiPackageReader()->fromFilesystem(self::PACKAGE_DIR);

        $this->assertTrue($reloaded->hasFile('resources/logo.png'));
        $this->assertSame($pngBytes, $reloaded->getFile('resources/logo.png')->getContent()->getContent());
        $this->assertTrue($reloaded->hasResource('RESOURCE001'));
        $this->assertContains('RESOURCE001', $this->dependencyRefsOf($reloaded, 'ITEM001'));
    }

    /**
     * @return list<string>
     */
    private function dependencyRefsOf(\Qti3\Package\Model\QtiPackage $package, string $resourceIdentifier): array
    {
        $dom = new \DOMDocument();
        $dom->loadXML((string) $package->manifest);

        foreach ($dom->getElementsByTagNameNS(self::MANIFEST_NAMESPACE, 'resource') as $resource) {
            if ($resource->getAttribute('identifier') !== $resourceIdentifier) {
                continue;
            }

            $refs = [];
            foreach ($resource->getElementsByTagNameNS(self::MANIFEST_NAMESPACE, 'dependency') as $dependency) {
                $refs[] = $dependency->getAttribute('identifierref');
            }

            return $refs;
        }

        return [];
    }

    private function itemXmlWithImage(string $identifier, string $src): string
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<qti-assessment-item xmlns="%s" identifier="%s" title="Vraag" time-dependent="false">'
            . '<qti-item-body><p><img src="%s" alt="Logo"/></p></qti-item-body></qti-assessment-item>',
            self::ASI_NAMESPACE,
            $identifier,
            $src,
        );
    }

    private function seedPackageOnDisk(): void
    {
        $dir = $this->tempDataDir . '/' . self::PACKAGE_DIR;
        mkdir($dir, 0777, true);

        $manifest = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest xmlns="%s" identifier="MANIFEST-1"><organizations/><resources>'
            . '<resource identifier="%s" type="imsqti_test_xmlv3p0" href="AssessmentTest.xml"><file href="AssessmentTest.xml"/>'
            . '<dependency identifierref="ITEM001"/><dependency identifierref="ITEM002"/></resource>'
            . '<resource identifier="ITEM001" type="imsqti_item_xmlv3p0" href="ITEM001.xml"><file href="ITEM001.xml"/></resource>'
            . '<resource identifier="ITEM002" type="imsqti_item_xmlv3p0" href="ITEM002.xml"><file href="ITEM002.xml"/></resource>'
            . '</resources></manifest>',
            self::MANIFEST_NAMESPACE,
            self::TEST_ID,
        );

        $test = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<qti-assessment-test xmlns="%s" identifier="test-1" title="Toets">'
            . '<qti-test-part identifier="tp" navigation-mode="linear" submission-mode="individual">'
            . '<qti-assessment-section identifier="s" title="" visible="true">'
            . '<qti-assessment-item-ref identifier="ITEM001" href="ITEM001.xml"/>'
            . '<qti-assessment-item-ref identifier="ITEM002" href="ITEM002.xml"/>'
            . '</qti-assessment-section></qti-test-part></qti-assessment-test>',
            self::ASI_NAMESPACE,
        );

        file_put_contents($dir . '/imsmanifest.xml', $manifest);
        file_put_contents($dir . '/AssessmentTest.xml', $test);
        file_put_contents($dir . '/ITEM001.xml', $this->itemXml('ITEM001'));
        file_put_contents($dir . '/ITEM002.xml', $this->itemXml('ITEM002'));
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
}
