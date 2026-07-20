<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service;

use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\Package\Model\FileContent\ExternalFileContent;
use Qti3\Package\Model\FileContent\IFileContent;
use Qti3\Package\Model\FileContent\LocalFileContent;
use Qti3\Package\Model\Manifest\ManifestResourceDependency;
use Qti3\Package\Model\Manifest\ManifestResourceDependencyCollection;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Model\Resource\ResourceCollection;
use Qti3\Package\Model\Resource\ResourceType;
use Qti3\Package\Model\Resource\Warnings;
use Qti3\Package\Model\Resource\Webcontent;
use Qti3\Package\Model\Resource\WebcontentCollection;
use Qti3\Package\Downloader\Resource\IResourceDownloader;
use Qti3\Package\Validator\Resource\IResourceValidator;
use Qti3\AssessmentItem\Service\ItemResourceBuilder;
use Qti3\Package\Service\Manifest\ManifestBuilder;
use Qti3\AssessmentTest\Service\TestResourceBuilder;
use Qti3\Shared\Model\IQtiResourceProvider;
use Qti3\Shared\Model\IXmlElement;
use Qti3\Shared\Model\QtiResource;
use Qti3\Shared\Collection\StringCollection;
use Exception;

class QtiPackageBuilder
{
    public function __construct(
        private readonly ManifestBuilder $manifestBuilder,
        private readonly TestResourceBuilder $testResourceBuilder,
        private readonly ItemResourceBuilder $itemResourceBuilder,
        private readonly IResourceValidator $resourceValidator,
        private readonly IResourceDownloader $resourceDownloader,
    ) {}

    /**
     * Generate a package from the typed models.
     *
     * When `$sourcePackage` is given (editing an existing package), files that
     * already live in that package are carried over as they are: media
     * referenced by relative path keeps its path and bytes, metadata
     * resources and their dependency on the test resource are copied, and the
     * test resource keeps its identifier.
     *
     * @param array<int,AssessmentItem> $assessmentItems
     */
    public function buildForTest(
        AssessmentTest $assessmentTest,
        array $assessmentItems,
        ?QtiPackage $sourcePackage = null,
    ): QtiPackage {
        $assessmentTest->validateItems($assessmentItems);

        $resources = new ResourceCollection();

        $warnings = new StringCollection();
        $webcontent = new WebcontentCollection();

        $dependencies = $this->processWebcontent($webcontent, $assessmentTest, $warnings, $sourcePackage);
        foreach ($this->sourceMetadataDependencies($sourcePackage) as $metadataDependency) {
            $dependencies->add($metadataDependency);
        }
        $sourceTestResource = $this->sourceTestResource($sourcePackage);
        $resources->add($this->testResourceBuilder->build(
            $assessmentTest,
            $dependencies,
            $sourceTestResource?->identifier ?? TestResourceBuilder::DEFAULT_IDENTIFIER,
            $sourceTestResource?->href ?? TestResourceBuilder::ASSESSMENT_TEST_FILE_NAME,
        ));

        foreach ($assessmentItems as $assessmentItem) {
            $itemRef = $assessmentTest->findItemRef($assessmentItem->identifier());
            $dependencies = $this->processWebcontent($webcontent, $assessmentItem, $warnings, $sourcePackage);

            $resources->add($this->itemResourceBuilder->build(
                (string) $itemRef->identifier,
                $assessmentItem,
                $dependencies,
                $itemRef->href,
            ));
        }
        foreach ($webcontent as $webcontentFile) {
            $resources->add($webcontentFile);
        }
        foreach ($this->sourceMetadataResources($sourcePackage) as $metadataResource) {
            $resources->add($metadataResource);
        }
        if ($warnings->count() > 0) {
            $resources->add(new Warnings($warnings));
        }

        return new QtiPackage(
            $resources,
            $this->manifestBuilder->buildForResources($resources),
        );
    }

    /**
     * @return array<int,QtiResource>
     */
    private function getQtiResources(IXmlElement $element, StringCollection $warnings, ?QtiPackage $sourcePackage): array
    {
        $resources = [];

        foreach ($element->children() as $child) {
            if ($child instanceof IQtiResourceProvider) {
                $this->processResourceProvider($child, $warnings, $sourcePackage);
                $resource = $child->getResource();
                if ($resource !== null) {
                    $resources[] = $resource;
                }
            }
            if ($child instanceof IXmlElement) {
                $resources = [...$resources, ...$this->getQtiResources($child, $warnings, $sourcePackage)];
            }
        }

        return $resources;
    }

    private function processWebcontent(
        WebcontentCollection $webcontent,
        IXmlElement $element,
        StringCollection $warnings,
        ?QtiPackage $sourcePackage,
    ): ManifestResourceDependencyCollection {
        $qtiResources = $this->getQtiResources($element, $warnings, $sourcePackage);

        $dependencies = new ManifestResourceDependencyCollection();
        foreach ($qtiResources as $qtiResource) {
            $webcontentFile = $webcontent->findByOriginalPath($qtiResource->originalPath);
            if (!$webcontentFile) {
                $webcontentFile = new Webcontent(
                    $qtiResource->originalPath,
                    sprintf('RESOURCE%03d', $webcontent->count() + 1),
                    $qtiResource->relativePath . $qtiResource->filename,
                    $this->contentFor($qtiResource->originalPath, $sourcePackage),
                    $qtiResource->isBinary,
                );
                $webcontent->add($webcontentFile);
            }
            $dependencies->add(new ManifestResourceDependency($webcontentFile->identifier));
        }
        return $dependencies;
    }

    private function processResourceProvider(
        IQtiResourceProvider $resourceProvider,
        StringCollection $warnings,
        ?QtiPackage $sourcePackage,
    ): void {
        $source = $resourceProvider->getSource();
        if (!$source || str_starts_with($source, 'data:')) {
            return;
        }

        // A file that already lives in the package being edited is carried
        // over as-is: keep its path so references in the regenerated XML stay
        // valid, and skip validation/download.
        if ($sourcePackage?->hasFile($source)) {
            $resourceProvider->setResource(new QtiResource(
                type: 'webcontent',
                originalPath: $source,
                relativePath: $this->relativeDirectory($source),
                filename: basename($source),
                isBinary: $resourceProvider->isBinary(),
            ));
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
            isBinary: $resourceProvider->isBinary(),
        );
        try {
            $this->resourceValidator->validate($resource);
            $resourceProvider->setResource($resource);
        } catch (Exception $e) {
            $warnings->add($e->getMessage());
        }
    }

    private function contentFor(string $originalPath, ?QtiPackage $sourcePackage): IFileContent
    {
        if ($sourcePackage?->hasFile($originalPath)) {
            return $sourcePackage->getFile($originalPath)->getContent();
        }
        if (preg_match('~^https?://~i', $originalPath) === 1) {
            return new ExternalFileContent($originalPath, $this->resourceDownloader);
        }

        return new LocalFileContent($originalPath);
    }

    private function relativeDirectory(string $path): string
    {
        $directory = dirname($path);

        return $directory === '.' ? '' : $directory . '/';
    }

    private function sourceTestResource(?QtiPackage $sourcePackage): ?Resource
    {
        return $sourcePackage?->resources->filterByType(ResourceType::ASSESSMENT_TEST)->first();
    }

    /**
     * @return array<int, Resource>
     */
    private function sourceMetadataResources(?QtiPackage $sourcePackage): array
    {
        return $sourcePackage?->resources->filterByType(ResourceType::RESOURCE_METADATA)->all() ?? [];
    }

    /**
     * @return array<int, ManifestResourceDependency>
     */
    private function sourceMetadataDependencies(?QtiPackage $sourcePackage): array
    {
        $sourceTest = $this->sourceTestResource($sourcePackage);
        if ($sourceTest === null) {
            return [];
        }

        $metadataIdentifiers = array_map(
            static fn(Resource $resource): string => $resource->identifier,
            $this->sourceMetadataResources($sourcePackage),
        );

        $dependencies = [];
        foreach ($sourceTest->resourceDependencies as $dependency) {
            if (in_array($dependency->identifierref, $metadataIdentifiers, true)) {
                $dependencies[] = new ManifestResourceDependency($dependency->identifierref);
            }
        }

        return $dependencies;
    }
}
