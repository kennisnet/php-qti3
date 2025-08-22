<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItem;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItemId;
use App\SharedKernel\Domain\Qti\AssessmentItem\Repository\IAssessmentItemRepository;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTest;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTestId;
use App\SharedKernel\Domain\Qti\AssessmentTest\Repository\IAssessmentTestRepository;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependency;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Warnings;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Webcontent;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\WebcontentCollection;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\IResourceValidator;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\ItemResourceBuilder;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\Manifest\ManifestBuilder;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\TestResourceBuilder;
use App\SharedKernel\Domain\Qti\Shared\Model\IQtiResourceProvider;
use App\SharedKernel\Domain\Qti\Shared\Model\IXmlElement;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiResource;
use App\SharedKernel\Domain\StringCollection;
use App\SharedKernel\Infrastructure\Filesystem\IResourceDownloader;
use Exception;

class QtiPackageBuilder
{
    public function __construct(
        private readonly ManifestBuilder $manifestBuilder,
        private readonly TestResourceBuilder $testResourceBuilder,
        private readonly ItemResourceBuilder $itemResourceBuilder,
        private readonly IAssessmentTestRepository $assessmentTestRepository,
        private readonly IAssessmentItemRepository $assessmentItemRepository,
        private readonly IResourceValidator $resourceValidator,
        private readonly IResourceDownloader $resourceDownloader,
    ) {}

    public function buildFromAssessmentId(
        AssessmentTestId $assessmentTestId
    ): QtiPackage {
        $assessmentTest = $this->assessmentTestRepository->getById($assessmentTestId);

        /** @var array<int,AssessmentItemId> $itemIds */
        $itemIds = array_filter(array_map(
            fn($itemRef): ?AssessmentItemId => $itemRef->itemId,
            $assessmentTest->getItemRefs()
        ), fn($value): bool => $value !== null);

        $assessmentItems = $this->assessmentItemRepository->getByIds($itemIds);

        return $this->buildForTest($assessmentTest, $assessmentItems);
    }

    /**
     * @param array<int,AssessmentItem> $assessmentItems
     */
    public function buildForTest(
        AssessmentTest $assessmentTest,
        array $assessmentItems
    ): QtiPackage {
        $assessmentTest->validateItems($assessmentItems);

        $resources = new ResourceCollection();

        $warnings = new StringCollection();
        $webcontent = new WebcontentCollection();

        $dependencies = $this->processWebcontent($webcontent, $assessmentTest, $warnings);
        $resources->add($this->testResourceBuilder->build($assessmentTest, $dependencies));

        foreach ($assessmentItems as $assessmentItem) {
            $itemRef = $assessmentTest->findItemRef($assessmentItem->identifier());
            $dependencies = $this->processWebcontent($webcontent, $assessmentItem, $warnings);

            $resources->add($this->itemResourceBuilder->build(
                $itemRef->identifier,
                $assessmentItem,
                $dependencies
            ));
        }
        foreach ($webcontent as $webcontentFile) {
            $resources->add($webcontentFile);
        }
        if ($warnings->count() > 0) {
            $resources->add(new Warnings($warnings));
        }

        return new QtiPackage(
            $resources,
            $this->manifestBuilder->buildForResources($resources)
        );
    }

    /**
     * @return array<int,QtiResource>
     */
    private function getQtiResources(IXmlElement $element, StringCollection $warnings): array
    {
        $resources = [];

        foreach ($element->children() as $child) {
            if ($child instanceof IQtiResourceProvider) {
                $this->processResourceProvider($child, $warnings);
                $resource = $child->getResource();
                if ($resource !== null) {
                    $resources[] = $resource;
                }
            }
            if ($child instanceof IXmlElement) {
                $resources = [...$resources, ...$this->getQtiResources($child, $warnings)];
            }
        }

        return $resources;
    }

    private function processWebcontent(
        WebcontentCollection $webcontent,
        IXmlElement $element,
        StringCollection $warnings
    ): ManifestResourceDependencyCollection {
        $qtiResources = $this->getQtiResources($element, $warnings);

        $dependencies = new ManifestResourceDependencyCollection();
        foreach ($qtiResources as $qtiResource) {
            $webcontentFile = $webcontent->findByOriginalPath($qtiResource->originalPath);
            if (!$webcontentFile) {
                $webcontentFile = new Webcontent(
                    $qtiResource->originalPath,
                    sprintf('RESOURCE%03d', $webcontent->count() + 1),
                    $qtiResource->relativePath . $qtiResource->filename,
                    $this->resourceDownloader,
                    $qtiResource->isBinary
                );
                $webcontent->add($webcontentFile);
            }
            $dependencies->add(new ManifestResourceDependency($webcontentFile->identifier));
        }
        return $dependencies;
    }

    private function processResourceProvider(IQtiResourceProvider $resourceProvider, StringCollection $warnings): void
    {
        $source = $resourceProvider->getSource();
        if (!$source || str_starts_with($source, 'data:')) {
            return;
        }
        $filename =
            md5($source) . '.' .
            pathinfo($source, PATHINFO_EXTENSION);

        $resource = new QtiResource(
            type: 'webcontent',
            originalPath: $source,
            relativePath: 'resources/',
            filename: $filename,
            isBinary: $resourceProvider->isBinary()
        );
        try {
            $this->resourceValidator->validate($resource);
            $resourceProvider->setResource($resource);
        } catch (Exception $e) {
            $warnings->add($e->getMessage());
        }
    }
}
