<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Filesystem\Zip;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Filesystem\FileSystemUtils;
use Qti3\Package\Filesystem\Zip\QtiPackageVersionUpdater;
use RuntimeException;
use ZipArchive;

class QtiPackageVersionUpdaterTest extends TestCase
{
    private FileSystemUtils&MockObject $fileSystemUtils;
    private QtiPackageVersionUpdater $updater;

    protected function setUp(): void
    {
        $this->fileSystemUtils = $this->createMock(FileSystemUtils::class);
        $this->updater = new QtiPackageVersionUpdater($this->fileSystemUtils);
    }

    #[Test]
    public function updateVersionDowngradesSchemaVersionFrom301To300(): void
    {
        $zipFilePath = $this->createZip(__DIR__ . '/fixtures/imsmanifest_v3p1.xml');

        try {
            $tmpFilePath = '/tmp/qti_test_' . uniqid();
            $this->fileSystemUtils->expects($this->once())
                ->method('generateTempFilename')
                ->willReturn($tmpFilePath);

            $result = $this->updater->updateVersion($zipFilePath);

            $this->assertEquals($tmpFilePath . '.zip', $result);

            $zip = new ZipArchive();
            $zip->open($result);
            $manifestContent = $zip->getFromName('imsmanifest.xml');
            $zip->close();

            $this->assertIsString($manifestContent);
            $this->assertStringContainsString('3.0.0', $manifestContent);
            $this->assertStringNotContainsString('3.0.1', $manifestContent);
        } finally {
            if (isset($zipFilePath) && file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
            if (isset($result) && file_exists($result)) {
                unlink($result);
            }
        }
    }

    #[Test]
    public function updateVersionReplacesV3p1NamespacesWithV3p0(): void
    {
        $zipFilePath = $this->createZip(__DIR__ . '/fixtures/imsmanifest_v3p1.xml');

        try {
            $tmpFilePath = '/tmp/qti_test_' . uniqid();
            $this->fileSystemUtils->expects($this->once())
                ->method('generateTempFilename')
                ->willReturn($tmpFilePath);

            $result = $this->updater->updateVersion($zipFilePath);

            $zip = new ZipArchive();
            $zip->open($result);
            $manifestContent = $zip->getFromName('imsmanifest.xml');
            $zip->close();

            $this->assertIsString($manifestContent);
            $this->assertStringContainsString('qtiv3p0/imscp_v1p1', $manifestContent);
            $this->assertStringContainsString('imsqti_asiv3p0_v1p0.xsd', $manifestContent);
            $this->assertStringContainsString('imsqtiv3p0_imscpv1p2_v1p0.xsd', $manifestContent);
            $this->assertStringContainsString('imsqti_item_xmlv3p0', $manifestContent);
            $this->assertStringNotContainsString('v3p1', $manifestContent);
        } finally {
            if (isset($zipFilePath) && file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
            if (isset($result) && file_exists($result)) {
                unlink($result);
            }
        }
    }

    #[Test]
    public function updateVersionLeavesPackageWithVersion300Unchanged(): void
    {
        $manifestXml = '<manifest xmlns="http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1" identifier="M-001">'
            . '<metadata><schema>QTI Package</schema><schemaversion>3.0.0</schemaversion></metadata>'
            . '<organizations/><resources/></manifest>';
        $zipFilePath = $this->createZipWithContent($manifestXml);

        try {
            $tmpFilePath = '/tmp/qti_test_' . uniqid();
            $this->fileSystemUtils->expects($this->once())
                ->method('generateTempFilename')
                ->willReturn($tmpFilePath);

            $result = $this->updater->updateVersion($zipFilePath);

            $zip = new ZipArchive();
            $zip->open($result);
            $manifestContent = $zip->getFromName('imsmanifest.xml');
            $zip->close();

            $this->assertIsString($manifestContent);
            $this->assertStringContainsString('3.0.0', $manifestContent);
        } finally {
            if (isset($zipFilePath) && file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
            if (isset($result) && file_exists($result)) {
                unlink($result);
            }
        }
    }

    #[Test]
    public function cleanupRemovesTemporaryFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'qti_cleanup_test_') . '.zip';
        touch($tmpFile);
        $this->assertFileExists($tmpFile);

        $this->updater->cleanup($tmpFile);

        $this->assertFileDoesNotExist($tmpFile);
    }

    #[Test]
    public function updateVersionThrowsExceptionForInvalidZipFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not open zip file: /invalid/path/to/file.zip');

        $this->updater->updateVersion('/invalid/path/to/file.zip');
    }

    #[Test]
    public function updateVersionThrowsExceptionIfManifestNotFound(): void
    {
        $zipFilePath = $this->createZipWithoutManifest();

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('imsmanifest.xml not found in zip file');

            $this->updater->updateVersion($zipFilePath);
        } finally {
            if (file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
        }
    }

    #[Test]
    public function updateVersionThrowsExceptionForInvalidXml(): void
    {
        $zipFilePath = $this->createZipWithContent('<invalid-xml><metadata></metadata>');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Failed to load imsmanifest.xml');

            $this->updater->updateVersion($zipFilePath);
        } finally {
            if (file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
        }
    }

    #[Test]
    public function updateVersionThrowsExceptionIfSchemaVersionNotFound(): void
    {
        $zipFilePath = $this->createZipWithContent('<manifest><metadata></metadata></manifest>');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('schemaversion not found in imsmanifest.xml');

            $this->updater->updateVersion($zipFilePath);
        } finally {
            if (file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
        }
    }

    #[Test]
    public function updateVersionThrowsExceptionForUnsupportedSchemaVersion(): void
    {
        $xml = '<manifest><metadata><schemaversion>2.0.0</schemaversion></metadata></manifest>';
        $zipFilePath = $this->createZipWithContent($xml);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Invalid schema version. Expected 3.0.1 or 3.0.0');

            $this->updater->updateVersion($zipFilePath);
        } finally {
            if (file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
        }
    }

    private function createZipWithoutManifest(): string
    {
        $tmpZipPath = tempnam(sys_get_temp_dir(), 'qti_empty_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('empty.txt', '');
        $zip->close();
        return $tmpZipPath;
    }

    private function createZipWithContent(string $content): string
    {
        $tmpZipPath = tempnam(sys_get_temp_dir(), 'qti_content_') . '.zip';
        $tmpManifest = tempnam(sys_get_temp_dir(), 'qti_manifest_');
        file_put_contents($tmpManifest, $content);

        try {
            $zip = new ZipArchive();
            $zip->open($tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFile($tmpManifest, 'imsmanifest.xml');
            $zip->close();
        } finally {
            unlink($tmpManifest);
        }

        return $tmpZipPath;
    }

    private function createZip(string $manifestPath): string
    {
        $tmpZipPath = tempnam(sys_get_temp_dir(), 'qti_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile($manifestPath, 'imsmanifest.xml');
        $zip->close();
        return $tmpZipPath;
    }
}
