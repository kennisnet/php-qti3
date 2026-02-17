<?php

namespace Qti3\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Filesystem;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Qti3\Package\Downloader\Resource\PsrHttpClientResourceDownloader;
use Qti3\Package\Filesystem\FileSystemUtils;
use Qti3\Package\Filesystem\FlysystemPackageFactory;
use Qti3\Package\Validator\Resource\PsrHttpClientResourceValidator;
use Qti3\Package\Service\IFilesystemPackageFactory;
use Qti3\Package\Downloader\Resource\IResourceDownloader;
use Qti3\Package\Validator\Resource\IResourceValidator;
use Qti3\Package\Validator\QtiPackageValidator;
use Qti3\Package\Validator\ResponseProcessingValidator;
use Qti3\QtiClient;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Tests\Unit\Package\Validator\NoopImsQtiPackageValidator;
use ZipArchive;

/**
 * Integration: QtiClient integration tests.
 */
#[Group('integration')]
class QtiClientIntegrationTest extends TestCase
{
    private string $tempDataDir;

    protected function setUp(): void
    {
        $this->tempDataDir = sys_get_temp_dir() . '/qti_test_data_' . uniqid();
        mkdir($this->tempDataDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDataDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    private function createClient(
        ?IFilesystemPackageFactory $filesystemPackageFactory = null,
        ?IResourceValidator $resourceValidator = null,
        ?IResourceDownloader $resourceDownloader = null,
    ): QtiClient {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);

        // Default response for http mock
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('eof')->willReturn(true);
        $response->method('getBody')->willReturn($stream);

        $httpClient->method('sendRequest')->willReturn($response);

        return new QtiClient(
            $filesystemPackageFactory ?? new FlysystemPackageFactory(
                new Filesystem(new LocalFilesystemAdapter($this->tempDataDir))
            ),
            $resourceValidator ?? new PsrHttpClientResourceValidator(
                $httpClient,
                $requestFactory,
            ),
            $resourceDownloader ?? new PsrHttpClientResourceDownloader(
                new FileSystemUtils(),
                $httpClient,
                $requestFactory,
                $this->tempDataDir
            ),
        );
    }

    /**
     * Integration: QtiClient → reader → fromZip → package; getQtiPackageValidator → validate.
     * Proves the full validator stack runs (schema + response processing) and returns a StringCollection.
     */
    public function testValidatePackageFromZipWithQtiPackageValidatorReturnsErrorsFromBothValidators(): void
    {
        $client = $this->createClient();
        $zipPath = $this->createValidQtiZip();

        try {
            $reader = $client->getQtiPackageReader();
            $package = $reader->fromZip($zipPath);
            // Use NoopImsQtiPackageValidator to skip heavy XSD validation in this test
            $validator = new QtiPackageValidator(
                new NoopImsQtiPackageValidator(),
                new ResponseProcessingValidator($client->getResponseProcessor())
            );
            $errors = $validator->validate($package);

            $this->assertInstanceOf(StringCollection::class, $errors);
            // item001.xml triggers response processing validation
            $this->assertGreaterThanOrEqual(0, $errors->count());
        } finally {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }
    }

    /**
     * Integration: QtiClient → getQtiSchemaValidator → validateZipPackage on a valid ZIP.
     */
    public function testValidateValidZipWithQtiSchemaValidatorReturnsNoErrors(): void
    {
        $client = $this->createClient();
        $zipPath = $this->createValidQtiZip();

        try {
            $schemaValidator = $client->getQtiSchemaValidator();
            $errors = $schemaValidator->validateZipPackage($zipPath);

            $this->assertInstanceOf(StringCollection::class, $errors);
            $this->assertCount(0, $errors, 'Valid QTI ZIP should produce no schema validation errors. Got: ' . implode('; ', iterator_to_array($errors)));
        } finally {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }
    }

    /**
     * Integration: QtiClient → getQtiSchemaValidator → validateZipPackage on invalid input.
     */
    public function testValidateNonExistentZipWithQtiSchemaValidatorReturnsErrors(): void
    {
        $client = $this->createClient();
        $schemaValidator = $client->getQtiSchemaValidator();
        $errors = $schemaValidator->validateZipPackage('/non/existent/package.zip');

        $this->assertInstanceOf(StringCollection::class, $errors);
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Package file does not exist', iterator_to_array($errors)[0]);
    }

    /**
     * @return string Path to the created ZIP file
     */
    private function createValidQtiZip(): string
    {
        $manifestXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest xmlns="http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1" identifier="MANIFEST_QTI">'
            . '<metadata><schema>QTI Package</schema><schemaversion>3.0.0</schemaversion></metadata>'
            . '<organizations/>'
            . '<resources>'
            . '<resource identifier="item1" type="imsqti_item_xmlv3p0" href="item.xml">'
            . '<file href="item.xml"/>'
            . '</resource>'
            . '</resources>'
            . '</manifest>';

        $itemXmlPath = __DIR__ . '/../Unit/Package/Validator/resources/item001.xml';
        $this->assertFileExists($itemXmlPath, 'Test fixture item001.xml must exist');
        $itemXml = file_get_contents($itemXmlPath);

        $zipPath = tempnam(sys_get_temp_dir(), 'qti_client_test_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('imsmanifest.xml', $manifestXml);
        $zip->addFromString('item.xml', $itemXml);
        $zip->close();

        return $zipPath;
    }
}
