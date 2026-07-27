<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\Package\Model\IMediaSource;
use Qti3\Package\Model\Manifest\ManifestResourceDependency;
use Qti3\Package\Model\Manifest\ManifestResourceDependencyCollection;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Model\Resource\ResourceCollection;
use Qti3\Package\Model\Resource\ResourceType;
use Qti3\Package\Model\Resource\Warnings;
use Qti3\Package\Model\Resource\WebcontentCollection;
use Qti3\Package\Service\QtiPackageBuilder\ItemResourceBuilder;
use Qti3\Package\Service\QtiPackageBuilder\Manifest\ManifestBuilder;
use Qti3\Package\Service\QtiPackageBuilder\TestResourceBuilder;
use Qti3\Shared\Model\IXmlElement;
use Qti3\Shared\Collection\StringCollection;

class QtiPackageBuilder
{
    public function __construct(
        private readonly ManifestBuilder $manifestBuilder,
        private readonly TestResourceBuilder $testResourceBuilder,
        private readonly ItemResourceBuilder $itemResourceBuilder,
        private readonly WebcontentProcessor $webcontentProcessor,
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
     * Pass `$packageMediaSource` (the media files of this package's source,
     * addressed by package-relative path) when the source content references
     * media by local path: those files are read from the media source and
     * included as webcontent; any other local path is refused with a warning.
     *
     * @param array<int,AssessmentItem> $assessmentItems
     */
    public function buildForTest(
        AssessmentTest $assessmentTest,
        array $assessmentItems,
        ?QtiPackage $sourcePackage = null,
        ?IMediaSource $packageMediaSource = null,
    ): QtiPackage {
        $assessmentTest->validateItems($assessmentItems);

        $resources = new ResourceCollection();

        $warnings = new StringCollection();
        $webcontent = new WebcontentCollection();

        $dependencies = $this->webcontentProcessor->process($webcontent, $assessmentTest, $warnings, $sourcePackage, $packageMediaSource);
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
            $dependencies = $this->webcontentProcessor->process($webcontent, $assessmentItem, $warnings, $sourcePackage, $packageMediaSource);

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
