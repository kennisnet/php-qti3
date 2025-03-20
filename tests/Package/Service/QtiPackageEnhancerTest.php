<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Service;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\Manifest;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\TestResourceBuilder;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageEnhancer;
use App\SharedKernel\Infrastructure\Serializer\XmlBuilder;
use App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\QtiPackageMock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QtiPackageEnhancerTest extends TestCase
{
    #[Test]
    public function enhancePackageAddsTestResourceWhenMissing(): void
    {
        $package = new QtiPackageMock();

        $assessmentTestBuilder = new TestResourceBuilder(new XmlBuilder());
        $enhancer = new QtiPackageEnhancer($assessmentTestBuilder);

        // Act
        $enhancer->enhancePackage($package);

        // Assert
        self::assertCount(6, $package->resources);
        self::assertEquals(ResourceType::ASSESSMENT_TEST, $package->resources->first()->type);
    }

    #[Test]
    public function enhancePackageDoesNothingIfTestResourceExists(): void
    {
        // Arrange
        $package = new QtiPackageMock(
            resources: new ResourceCollection([
                new Resource('id', ResourceType::ASSESSMENT_TEST, 'test.xml', new ResourceFileCollection([
                    new ResourceFile('test.xml', new MemoryFileContent('content')),
                ]), new ManifestResourceDependencyCollection(), ),
            ]),
        );

        $assessmentTestBuilder = $this->createMock(TestResourceBuilder::class);
        $enhancer = new QtiPackageEnhancer($assessmentTestBuilder);

        // Act
        $enhancer->enhancePackage($package);

        // Assert
        self::assertCount(1, $package->resources);
        self::assertEquals(ResourceType::ASSESSMENT_TEST, $package->resources->first()->type);
    }

    #[Test]
    public function generateTestCreatesCorrectAssessmentTest(): void
    {
        // Arrange
        $manifest = $this->createMock(Manifest::class); // Maak een mock van het verwachte manifest

        $package = new QtiPackageMock(
            resources: new ResourceCollection(),
            manifest: $manifest,
        );

        $assessmentTestBuilder = new TestResourceBuilder(new XmlBuilder());
        $enhancer = new QtiPackageEnhancer($assessmentTestBuilder);

        // Act
        $enhancer->enhancePackage($package);

        $assessmentTest = $package->resources->filterByType(ResourceType::ASSESSMENT_TEST)->first();
        $xmlContent = (string) $assessmentTest->files->first()->getContent();

        // Assert
        self::assertStringContainsString('qti-assessment-section', $xmlContent);
    }

}
