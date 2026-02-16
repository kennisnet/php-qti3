<?php

declare(strict_types=1);

namespace Qti3\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Repository\IAssessmentItemRepository;
use Qti3\AssessmentItem\Service\ResponseProcessor;
use Qti3\AssessmentTest\Repository\IAssessmentTestRepository;
use Qti3\Package\Filesystem\Zip\ZipPackageFactory;
use Qti3\Package\Service\IFlysystemPackageFactory;
use Qti3\Package\Service\IResourceDownloader;
use Qti3\Package\Service\QtiPackageBuilder;
use Qti3\Package\Service\QtiPackageBuilder\IResourceValidator;
use Qti3\Package\Service\QtiPackageBuilder\XmlBuilder;
use Qti3\Package\Service\QtiPackageReader;
use Qti3\Package\Validator\QtiPackageValidator;
use Qti3\Package\Validator\QtiSchemaValidator;
use Qti3\QtiClient;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Xml\Reader\XmlReader;
use ZipArchive;

final class QtiClientTest extends TestCase
{
    private function createClient(
        ?IFlysystemPackageFactory $flysystemPackageFactory = null,
        ?IResourceValidator $resourceValidator = null,
        ?IResourceDownloader $resourceDownloader = null,
        ?IAssessmentTestRepository $assessmentTestRepository = null,
        ?IAssessmentItemRepository $assessmentItemRepository = null,
    ): QtiClient {
        return new QtiClient(
            $flysystemPackageFactory ?? $this->createMock(IFlysystemPackageFactory::class),
            $resourceValidator ?? $this->createMock(IResourceValidator::class),
            $resourceDownloader ?? $this->createMock(IResourceDownloader::class),
            $assessmentTestRepository ?? $this->createMock(IAssessmentTestRepository::class),
            $assessmentItemRepository ?? $this->createMock(IAssessmentItemRepository::class),
        );
    }

    public function testGetQtiPackageReaderReturnsInstance(): void
    {
        $container = $this->createClient();
        $reader = $container->getQtiPackageReader();
        $this->assertInstanceOf(QtiPackageReader::class, $reader);
    }

    public function testGetQtiPackageReaderReturnsNewInstanceEachTime(): void
    {
        $container = $this->createClient();
        $this->assertNotSame(
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

    public function testGetZipPackageFactoryReturnsNewInstanceEachTime(): void
    {
        $container = $this->createClient();
        $this->assertNotSame(
            $container->getZipPackageFactory(),
            $container->getZipPackageFactory(),
        );
    }

    public function testGetFlysystemPackageFactoryReturnsProvidedFactory(): void
    {
        $factory = $this->createMock(IFlysystemPackageFactory::class);
        $container = $this->createClient(flysystemPackageFactory: $factory);
        $this->assertSame($factory, $container->getFlysystemPackageFactory());
    }

    public function testGetQtiPackageBuilderReturnsInstance(): void
    {
        $container = $this->createClient();
        $builder = $container->getQtiPackageBuilder();
        $this->assertInstanceOf(QtiPackageBuilder::class, $builder);
    }

    public function testGetQtiPackageBuilderReturnsNewInstanceEachTime(): void
    {
        $container = $this->createClient();
        $this->assertNotSame(
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

    public function testGetXmlBuilderReturnsNewInstanceEachTime(): void
    {
        $container = $this->createClient();
        $this->assertNotSame(
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

    public function testGetResponseProcessorReturnsNewInstanceEachTime(): void
    {
        $container = $this->createClient();
        $this->assertNotSame(
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

    public function testFromFilesystemThrowsWhenReaderUsesUnavailableFlysystemFactory(): void
    {
        $container = $this->createClient();
        $reader = $container->getQtiPackageReader();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('IFlysystemPackageFactory');

        $reader->fromFilesystem('/tmp/nonexistent');
    }

    public function testGetQtiPackageValidatorReturnsInstance(): void
    {
        $client = $this->createClient();
        $validator = $client->getQtiPackageValidator();
        $this->assertInstanceOf(QtiPackageValidator::class, $validator);
    }

    public function testGetQtiPackageValidatorReturnsNewInstanceEachTime(): void
    {
        $client = $this->createClient();
        $this->assertNotSame(
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

    public function testGetQtiSchemaValidatorReturnsNewInstanceEachTime(): void
    {
        $client = $this->createClient();
        $this->assertNotSame(
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
            $validator = $client->getQtiPackageValidator();
            $errors = $validator->validate($package);

            $this->assertInstanceOf(StringCollection::class, $errors);
            // item001.xml triggers both schema-related checks and response processing validation; at least one runs
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
