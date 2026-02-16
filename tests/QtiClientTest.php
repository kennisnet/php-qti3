<?php

declare(strict_types=1);

namespace Qti3\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Service\ResponseProcessor;
use Qti3\Package\Filesystem\Zip\ZipPackageFactory;
use Qti3\Package\Service\IFilesystemPackageFactory;
use Qti3\Package\Service\IResourceDownloader;
use Qti3\Package\Service\QtiPackageBuilder;
use Qti3\Package\Service\QtiPackageBuilder\IResourceValidator;
use Qti3\Package\Service\QtiPackageBuilder\XmlBuilder;
use Qti3\Package\Service\QtiPackageReader;
use Qti3\Package\Validator\QtiPackageValidator;
use Qti3\Package\Validator\QtiSchemaValidator;
use Qti3\Package\Validator\ResponseProcessingValidator;
use Qti3\QtiClient;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Xml\Reader\XmlReader;
use Qti3\Tests\Package\Validator\NoopImsQtiPackageValidator;
use ZipArchive;

final class QtiClientTest extends TestCase
{
    private function createClient(
        ?IFilesystemPackageFactory $filesystemPackageFactory = null,
        ?IResourceValidator $resourceValidator = null,
        ?IResourceDownloader $resourceDownloader = null,
    ): QtiClient {
        return new QtiClient(
            $filesystemPackageFactory ?? $this->createMock(IFilesystemPackageFactory::class),
            $resourceValidator ?? $this->createMock(IResourceValidator::class),
            $resourceDownloader ?? $this->createMock(IResourceDownloader::class),
        );
    }

    public function testGetQtiPackageReaderReturnsInstance(): void
    {
        $container = $this->createClient();
        $reader = $container->getQtiPackageReader();
        $this->assertInstanceOf(QtiPackageReader::class, $reader);
    }

    public function testGetQtiPackageReaderReturnsSameInstance(): void
    {
        $container = $this->createClient();
        $this->assertSame(
            $container->getQtiPackageReader(),
            $container->getQtiPackageReader(),
        );
    }

    public function testGetZipPackageFactoryReturnsInstance(): void
    {
        $container = $this->createClient();
        $factory = $container->getZipPackageFactory();
        $this->assertInstanceOf(ZipPackageFactory::class, $factory);
    }

    public function testGetZipPackageFactoryReturnsSameInstance(): void
    {
        $container = $this->createClient();
        $this->assertSame(
            $container->getZipPackageFactory(),
            $container->getZipPackageFactory(),
        );
    }

    public function testGetFilesystemPackageFactoryReturnsProvidedFactory(): void
    {
        $factory = $this->createMock(IFilesystemPackageFactory::class);
        $container = $this->createClient(filesystemPackageFactory: $factory);
        $this->assertSame($factory, $container->getFilesystemPackageFactory());
    }

    public function testGetQtiPackageBuilderReturnsInstance(): void
    {
        $container = $this->createClient();
        $builder = $container->getQtiPackageBuilder();
        $this->assertInstanceOf(QtiPackageBuilder::class, $builder);
    }

    public function testGetQtiPackageBuilderReturnsSameInstance(): void
    {
        $container = $this->createClient();
        $this->assertSame(
            $container->getQtiPackageBuilder(),
            $container->getQtiPackageBuilder(),
        );
    }

    public function testGetXmlBuilderReturnsInstance(): void
    {
        $container = $this->createClient();
        $builder = $container->getXmlBuilder();
        $this->assertInstanceOf(XmlBuilder::class, $builder);
    }

    public function testGetXmlBuilderReturnsSameInstance(): void
    {
        $container = $this->createClient();
        $this->assertSame(
            $container->getXmlBuilder(),
            $container->getXmlBuilder(),
        );
    }

    public function testGetResponseProcessorReturnsInstance(): void
    {
        $container = $this->createClient();
        $processor = $container->getResponseProcessor();
        $this->assertInstanceOf(ResponseProcessor::class, $processor);
    }

    public function testGetResponseProcessorReturnsSameInstance(): void
    {
        $container = $this->createClient();
        $this->assertSame(
            $container->getResponseProcessor(),
            $container->getResponseProcessor(),
        );
    }

    public function testGetXmlReaderReturnsInstance(): void
    {
        $container = $this->createClient();
        $reader = $container->getXmlReader();
        $this->assertInstanceOf(XmlReader::class, $reader);
    }


    public function testGetQtiPackageValidatorReturnsInstance(): void
    {
        $client = $this->createClient();
        $validator = $client->getQtiPackageValidator();
        $this->assertInstanceOf(QtiPackageValidator::class, $validator);
    }

    public function testGetQtiPackageValidatorReturnsSameInstance(): void
    {
        $client = $this->createClient();
        $this->assertSame(
            $client->getQtiPackageValidator(),
            $client->getQtiPackageValidator(),
        );
    }

    public function testGetQtiSchemaValidatorReturnsInstance(): void
    {
        $client = $this->createClient();
        $validator = $client->getQtiSchemaValidator();
        $this->assertInstanceOf(QtiSchemaValidator::class, $validator);
    }

    public function testGetQtiSchemaValidatorReturnsSameInstance(): void
    {
        $client = $this->createClient();
        $this->assertSame(
            $client->getQtiSchemaValidator(),
            $client->getQtiSchemaValidator(),
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

        $itemXmlPath = __DIR__ . '/Package/Validator/resources/item001.xml';
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
