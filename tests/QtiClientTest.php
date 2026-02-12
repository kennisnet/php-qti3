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
use Qti3\QtiClient;
use Qti3\Shared\Xml\Reader\XmlReader;

final class QtiClientTest extends TestCase
{
    public function testGetQtiPackageReaderReturnsInstance(): void
    {
        $container = new QtiClient();

        $reader = $container->getQtiPackageReader();

        $this->assertInstanceOf(QtiPackageReader::class, $reader);
    }

    public function testGetQtiPackageReaderReturnsSameInstance(): void
    {
        $container = new QtiClient();

        $this->assertSame(
            $container->getQtiPackageReader(),
            $container->getQtiPackageReader(),
        );
    }

    public function testGetZipPackageFactoryReturnsInstance(): void
    {
        $container = new QtiClient();

        $factory = $container->getZipPackageFactory();

        $this->assertInstanceOf(ZipPackageFactory::class, $factory);
    }

    public function testGetZipPackageFactoryReturnsSameInstance(): void
    {
        $container = new QtiClient();

        $this->assertSame(
            $container->getZipPackageFactory(),
            $container->getZipPackageFactory(),
        );
    }

    public function testGetFlysystemPackageFactoryThrowsWhenNotConfigured(): void
    {
        $container = new QtiClient();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('IFlysystemPackageFactory');

        $container->getFlysystemPackageFactory();
    }

    public function testGetFlysystemPackageFactoryReturnsProvidedFactory(): void
    {
        $factory = $this->createMock(IFlysystemPackageFactory::class);
        $container = new QtiClient(flysystemPackageFactory: $factory);

        $this->assertSame($factory, $container->getFlysystemPackageFactory());
    }

    public function testGetQtiPackageBuilderThrowsWhenResourceValidatorMissing(): void
    {
        $container = new QtiClient(
            resourceDownloader: $this->createMock(IResourceDownloader::class),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('IResourceValidator');

        $container->getQtiPackageBuilder();
    }

    public function testGetQtiPackageBuilderThrowsWhenResourceDownloaderMissing(): void
    {
        $container = new QtiClient(
            resourceValidator: $this->createMock(IResourceValidator::class),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('IResourceDownloader');

        $container->getQtiPackageBuilder();
    }

    public function testGetQtiPackageBuilderReturnsInstance(): void
    {
        $container = new QtiClient(
            resourceValidator: $this->createMock(IResourceValidator::class),
            resourceDownloader: $this->createMock(IResourceDownloader::class),
        );

        $builder = $container->getQtiPackageBuilder();

        $this->assertInstanceOf(QtiPackageBuilder::class, $builder);
    }

    public function testGetQtiPackageBuilderReturnsSameInstance(): void
    {
        $container = new QtiClient(
            resourceValidator: $this->createMock(IResourceValidator::class),
            resourceDownloader: $this->createMock(IResourceDownloader::class),
        );

        $this->assertSame(
            $container->getQtiPackageBuilder(),
            $container->getQtiPackageBuilder(),
        );
    }

    public function testGetXmlBuilderReturnsInstance(): void
    {
        $container = new QtiClient();

        $builder = $container->getXmlBuilder();

        $this->assertInstanceOf(XmlBuilder::class, $builder);
    }

    public function testGetXmlBuilderReturnsSameInstance(): void
    {
        $container = new QtiClient();

        $this->assertSame(
            $container->getXmlBuilder(),
            $container->getXmlBuilder(),
        );
    }

    public function testGetResponseProcessorReturnsInstance(): void
    {
        $container = new QtiClient();

        $processor = $container->getResponseProcessor();

        $this->assertInstanceOf(ResponseProcessor::class, $processor);
    }

    public function testGetResponseProcessorReturnsSameInstance(): void
    {
        $container = new QtiClient();

        $this->assertSame(
            $container->getResponseProcessor(),
            $container->getResponseProcessor(),
        );
    }

    public function testGetXmlReaderReturnsInstance(): void
    {
        $container = new QtiClient();

        $reader = $container->getXmlReader();

        $this->assertInstanceOf(XmlReader::class, $reader);
    }

    public function testFromFilesystemThrowsWithoutFlysystemFactory(): void
    {
        $container = new QtiClient();
        $reader = $container->getQtiPackageReader();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('IFlysystemPackageFactory');

        $reader->fromFilesystem('/tmp/nonexistent');
    }
}
