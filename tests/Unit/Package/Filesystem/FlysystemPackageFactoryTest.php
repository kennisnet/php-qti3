<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Filesystem;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Filesystem\FlysystemPackageFactory;
use Qti3\Package\Model\IItemEditor;
use Qti3\Package\Model\IPackageWriter;
use Qti3\Package\Model\Item\EditedItem;

final class FlysystemPackageFactoryTest extends TestCase
{
    private const string ASI_NAMESPACE = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';
    private const string MANIFEST_NAMESPACE = 'http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1';

    private FlysystemPackageFactory $factory;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $this->factory = new FlysystemPackageFactory($this->filesystem);
    }

    #[Test]
    public function itVendsAWriter(): void
    {
        $this->assertInstanceOf(IPackageWriter::class, $this->factory->getWriter('folder'));
    }

    #[Test]
    public function itVendsAWorkingItemEditorBoundToTheFolder(): void
    {
        $this->seedEmptyDraft('qti/v1');

        $editor = $this->factory->getItemEditor('qti/v1');

        $this->assertInstanceOf(IItemEditor::class, $editor);

        $editedItem = $editor->addItem($this->itemXml());
        $this->assertInstanceOf(EditedItem::class, $editedItem);
        $this->assertSame('ITEM001', $editedItem->identifier);
        $this->assertTrue($this->filesystem->fileExists('qti/v1/ITEM001.xml'));
    }

    private function seedEmptyDraft(string $folder): void
    {
        $this->filesystem->write(
            $folder . '/imsmanifest.xml',
            sprintf(
                '<manifest xmlns="%s" identifier="M"><resources>'
                . '<resource identifier="test" type="imsqti_test_xmlv3p0" href="AssessmentTest.xml">'
                . '<file href="AssessmentTest.xml"/></resource></resources></manifest>',
                self::MANIFEST_NAMESPACE,
            ),
        );

        $this->filesystem->write(
            $folder . '/AssessmentTest.xml',
            sprintf(
                '<qti-assessment-test xmlns="%s" identifier="test-1" title="">'
                . '<qti-test-part identifier="testPart-1" navigation-mode="linear" submission-mode="individual">'
                . '<qti-assessment-section identifier="section-1" title="" visible="true"/>'
                . '</qti-test-part></qti-assessment-test>',
                self::ASI_NAMESPACE,
            ),
        );
    }

    private function itemXml(): string
    {
        return sprintf(
            '<qti-assessment-item xmlns="%s" identifier="X" title="Vraag" time-dependent="false"><qti-item-body/></qti-assessment-item>',
            self::ASI_NAMESPACE,
        );
    }
}
