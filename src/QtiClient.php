<?php

declare(strict_types=1);

namespace Qti3;

use LogicException;
use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\AssessmentItem\Model\AssessmentItemId;
use Qti3\AssessmentItem\Repository\IAssessmentItemRepository;
use Qti3\AssessmentItem\Service\AssessmentItemDeterminator;
use Qti3\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use Qti3\AssessmentItem\Service\Parser\ProcessingElementParser;
use Qti3\AssessmentItem\Service\Parser\QtiExpressionParser;
use Qti3\AssessmentItem\Service\Parser\ResponseDeclarationParser;
use Qti3\AssessmentItem\Service\Parser\ResponseProcessingParser;
use Qti3\AssessmentItem\Service\ResponseProcessor;
use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\AssessmentTest\Model\AssessmentTestId;
use Qti3\AssessmentTest\Repository\IAssessmentTestRepository;
use Qti3\Package\Filesystem\FileSystemUtils;
use Qti3\Package\Filesystem\Zip\ZipArchiveFactory;
use Qti3\Package\Filesystem\Zip\ZipPackageFactory;
use Qti3\Package\Model\IPackageReader;
use Qti3\Package\Model\IPackageWriter;
use Qti3\Package\Model\Manifest\ManifestFactory;
use Qti3\Package\Service\IFlysystemPackageFactory;
use Qti3\Package\Service\IResourceDownloader;
use Qti3\Package\Service\IZipPackageFactory;
use Qti3\Package\Service\QtiPackageBuilder;
use Qti3\Package\Service\QtiPackageBuilder\IResourceValidator;
use Qti3\Package\Service\QtiPackageBuilder\IXmlBuilder;
use Qti3\Package\Service\QtiPackageBuilder\ItemResourceBuilder;
use Qti3\Package\Service\QtiPackageBuilder\Manifest\ManifestBuilder;
use Qti3\Package\Service\QtiPackageBuilder\Manifest\MetadataBuilder;
use Qti3\Package\Service\QtiPackageBuilder\Manifest\OrganizationsBuilder;
use Qti3\Package\Service\QtiPackageBuilder\Manifest\ResourcesBuilder;
use Qti3\Package\Service\QtiPackageBuilder\TestResourceBuilder;
use Qti3\Package\Service\QtiPackageBuilder\XmlBuilder;
use Qti3\Package\Service\QtiPackageReader;
use Qti3\Package\Validator\QtiPackageValidator;
use Qti3\Package\Validator\QtiSchemaValidator;
use Qti3\Package\Validator\ResponseProcessingValidator;
use Qti3\Shared\Xml\Reader\IXmlReader;
use Qti3\Shared\Xml\Reader\XmlReader;

final readonly class QtiClient
{
    public function __construct(
        private IFlysystemPackageFactory $flysystemPackageFactory,
        private IResourceValidator $resourceValidator,
        private IResourceDownloader $resourceDownloader,
        private IAssessmentTestRepository $assessmentTestRepository,
        private IAssessmentItemRepository $assessmentItemRepository,
    ) {}

    public function getQtiPackageReader(): QtiPackageReader
    {
        return new QtiPackageReader(
            $this->getManifestFactory(),
            $this->getXmlReader(),
            $this->getZipPackageFactory(),
            $this->createUnavailableFlysystemFactory(),
        );
    }

    public function getZipPackageFactory(): IZipPackageFactory
    {
        return new ZipPackageFactory(
            new ZipArchiveFactory(),
            new FileSystemUtils(),
        );
    }

    public function getFlysystemPackageFactory(): IFlysystemPackageFactory
    {
        return $this->flysystemPackageFactory;
    }

    public function getQtiPackageBuilder(): QtiPackageBuilder
    {
        return new QtiPackageBuilder(
            $this->createManifestBuilder(),
            $this->createTestResourceBuilder(),
            $this->createItemResourceBuilder(),
            $this->assessmentTestRepository,
            $this->assessmentItemRepository,
            $this->resourceValidator,
            $this->resourceDownloader,
        );
    }

    public function getXmlBuilder(): IXmlBuilder
    {
        return new XmlBuilder();
    }

    public function getResponseProcessor(): ResponseProcessor
    {
        return new ResponseProcessor(
            new ResponseDeclarationParser(),
            new OutcomeDeclarationParser(),
            new ResponseProcessingParser(
                new ProcessingElementParser(
                    new QtiExpressionParser(),
                ),
            ),
            new AssessmentItemDeterminator(),
        );
    }

    public function getQtiPackageValidator(): QtiPackageValidator
    {
        return new QtiPackageValidator(
            new QtiSchemaValidator($this->getManifestFactory(), $this->getXmlReader()),
            new ResponseProcessingValidator($this->getResponseProcessor()),
        );
    }

    public function getQtiSchemaValidator(): QtiSchemaValidator
    {
        return new QtiSchemaValidator($this->getManifestFactory(), $this->getXmlReader());
    }

    public function getXmlReader(): IXmlReader
    {
        return new XmlReader();
    }

    private function getManifestFactory(): ManifestFactory
    {
        return new ManifestFactory($this->getXmlReader());
    }

    private function createManifestBuilder(): ManifestBuilder
    {
        return new ManifestBuilder(
            $this->getXmlBuilder(),
            new MetadataBuilder(),
            new OrganizationsBuilder(),
            new ResourcesBuilder(),
            $this->getXmlReader(),
        );
    }

    private function createTestResourceBuilder(): TestResourceBuilder
    {
        return new TestResourceBuilder(
            $this->getXmlBuilder(),
            $this->getXmlReader(),
        );
    }

    private function createItemResourceBuilder(): ItemResourceBuilder
    {
        return new ItemResourceBuilder(
            $this->getXmlBuilder(),
            $this->getXmlReader(),
        );
    }

    private function createUnavailableFlysystemFactory(): IFlysystemPackageFactory
    {
        return new class implements IFlysystemPackageFactory {
            public function getReader(string $folder, bool $lazyLoading = true): IPackageReader
            {
                throw new LogicException(
                    'requires an IFlysystemPackageFactory. Provide it via the Qti3 constructor.',
                );
            }

            public function getWriter(string $folder): IPackageWriter
            {
                throw new LogicException(
                    'requires an IFlysystemPackageFactory. Provide it via the Qti3 constructor.',
                );
            }
        };
    }

    private function createUnavailableTestRepository(): IAssessmentTestRepository
    {
        return new class implements IAssessmentTestRepository {
            public function getById(AssessmentTestId $assessmentTestId): AssessmentTest
            {
                throw new LogicException(
                    'IAssessmentTestRepository is required for this operation. Provide it via the Qti3 constructor.',
                );
            }
        };
    }

    private function createUnavailableItemRepository(): IAssessmentItemRepository
    {
        return new class implements IAssessmentItemRepository {
            public function getById(AssessmentItemId $assessmentItemId): AssessmentItem
            {
                throw new LogicException(
                    'IAssessmentItemRepository is required for this operation. Provide it via the Qti3 constructor.',
                );
            }

            public function getByIds(array $assessmentItemIds): array
            {
                throw new LogicException(
                    'IAssessmentItemRepository is required for this operation. Provide it via the Qti3 constructor.',
                );
            }
        };
    }
}
