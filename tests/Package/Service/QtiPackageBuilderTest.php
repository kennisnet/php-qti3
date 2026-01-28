<?php

declare(strict_types=1);

namespace Qti3\Tests\Package\Service;

use Qti3\AssessmentItem\Repository\IAssessmentItemRepository;
use Qti3\AssessmentTest\Repository\IAssessmentTestRepository;
use Qti3\Package\Model\FileContent\MemoryFileContent;
use Qti3\Package\Model\Manifest\ManifestResourceDependencyCollection;
use Qti3\Package\Model\PackageFile\PackageFile;
use Qti3\Package\Model\PackageFile\PackageFileCollection;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Model\Resource\ResourceType;
use Qti3\Package\Service\IResourceDownloader;
use Qti3\Package\Service\QtiPackageBuilder;
use Qti3\Package\Service\QtiPackageBuilder\IResourceValidator;
use Qti3\Package\Service\QtiPackageBuilder\ItemResourceBuilder;
use Qti3\Package\Service\QtiPackageBuilder\Manifest\ManifestBuilder;
use Qti3\Package\Service\QtiPackageBuilder\TestResourceBuilder;
use Qti3\Tests\AssessmentItem\Model\AssessmentItemStub;
use Qti3\Tests\AssessmentTest\Model\AssessmentTestStub;
use Qti3\Tests\Package\Model\Manifest\ManifestMock;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QtiPackageBuilderTest extends TestCase
{
    private QtiPackageBuilder $qtiPackageBuilder;
    private ManifestBuilder $manifestBuilder;
    private TestResourceBuilder $assessmentTestBuilder;
    private ItemResourceBuilder $assessmentItemBuilder;
    private IAssessmentTestRepository $assessmentTestRepository;
    private IAssessmentItemRepository $assessmentItemRepository;
    private IResourceValidator $resourceValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manifestBuilder = $this->createMock(ManifestBuilder::class);
        $this->assessmentTestBuilder = $this->createMock(TestResourceBuilder::class);
        $this->assessmentItemBuilder = $this->createMock(ItemResourceBuilder::class);
        $this->assessmentTestRepository = $this->createMock(IAssessmentTestRepository::class);
        $this->assessmentItemRepository = $this->createMock(IAssessmentItemRepository::class);
        $this->resourceValidator = $this->createMock(IResourceValidator::class);

        $this->qtiPackageBuilder = new QtiPackageBuilder(
            $this->manifestBuilder,
            $this->assessmentTestBuilder,
            $this->assessmentItemBuilder,
            $this->assessmentTestRepository,
            $this->assessmentItemRepository,
            $this->resourceValidator,
            $this->createMock(IResourceDownloader::class),
        );
    }

    #[Test]
    public function aQtiPackageCanBeCreatedFromAnAssessmentTest(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTestWithTwoItems();

        $manifest = ManifestMock::create();

        $manifestBuilder = $this->createMock(ManifestBuilder::class);
        $manifestBuilder->method('buildForResources')->willReturn($manifest);

        $assessmentTestBuilder = $this->createMock(TestResourceBuilder::class);
        $assessmentTestBuilder->method('build')->willReturn(
            new Resource('id', ResourceType::ASSESSMENT_TEST, 'test.xml', new PackageFileCollection([
                new PackageFile('test.xml', new MemoryFileContent('content')),
            ]), new ManifestResourceDependencyCollection()),
        );

        $qtiPackage = $this->qtiPackageBuilder->buildForTest($assessmentTest, [AssessmentItemStub::assessmentItemWithImage(), AssessmentItemStub::assessmentItemWithImage()]);

        $this->assertInstanceOf(QtiPackage::class, $qtiPackage);
    }

    #[Test]
    public function aQtiPackageCanBeCreatedFromAnAssessmentId(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTest();

        $this->assessmentTestRepository->method('getById')->willReturn($assessmentTest);
        $this->assessmentItemRepository->method('getByIds')->willReturn([AssessmentItemStub::assessmentItem()]);

        $qtiPackage = $this->qtiPackageBuilder->buildFromAssessmentId($assessmentTest->identifier);

        $this->assertInstanceOf(QtiPackage::class, $qtiPackage);
    }

    #[Test]
    public function aQtiPackageCanBeCreatedWithWarning(): void
    {
        $assessmentTest = AssessmentTestStub::assessmentTestWithTwoItems();

        $this->assessmentTestRepository->method('getById')->willReturn($assessmentTest);
        $this->assessmentItemRepository->method('getByIds')->willReturn(
            [AssessmentItemStub::assessmentItem(), AssessmentItemStub::assessmentItem()],
        );

        $this->resourceValidator->method('validate')->willThrowException(new Exception('Test exception'));

        $this->assessmentTestBuilder->method('build')->willReturn(
            new Resource('id', ResourceType::ASSESSMENT_TEST, 'test.xml', new PackageFileCollection([
                new PackageFile('test.xml', new MemoryFileContent('content')),
            ]), new ManifestResourceDependencyCollection()),
        );
        $this->assessmentItemBuilder->method('build')->willReturn(
            new Resource('id', ResourceType::ASSESSMENT_ITEM, 'item.xml', new PackageFileCollection([
                new PackageFile('item.xml', new MemoryFileContent('content')),
            ]), new ManifestResourceDependencyCollection()),
        );

        $qtiPackage = $this->qtiPackageBuilder->buildFromAssessmentId($assessmentTest->identifier);

        $this->assertInstanceOf(QtiPackage::class, $qtiPackage);

        $controlfiles = $qtiPackage->resources->filterByType(ResourceType::CONTROLFILE);
        $this->assertCount(1, $controlfiles);
    }
}
