<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Service;

use App\SharedKernel\Domain\Qti\AssessmentItem\Repository\IAssessmentItemRepository;
use App\SharedKernel\Domain\Qti\AssessmentTest\Repository\IAssessmentTestRepository;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFile;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceType;
use App\SharedKernel\Domain\Qti\Package\Service\IResourceDownloader;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\IResourceValidator;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\ItemResourceBuilder;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\Manifest\ManifestBuilder;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\TestResourceBuilder;
use App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItemStub;
use App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTestStub;
use App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestMock;
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
