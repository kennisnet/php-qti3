<?php

declare(strict_types=1);

namespace Qti3\Package\Model;

use Qti3\Package\Exception\InvalidQtiPackageException;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Exception\ResourceNotFoundException;
use Qti3\Package\Model\Manifest\Manifest;
use Qti3\Package\Model\Manifest\ManifestResource;
use Qti3\Package\Model\Metadata\Metadata;
use Qti3\Package\Model\PackageFile\AssessmentTestFile;
use Qti3\Package\Model\PackageFile\PackageFile;
use Qti3\Package\Model\PackageFile\PackageFileCollection;
use Qti3\Package\Model\PackageFile\XmlFile;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Model\Resource\ResourceCollection;
use Qti3\Package\Model\Resource\ResourceType;
use InvalidArgumentException;

class QtiPackage
{
    public function __construct(
        public readonly ResourceCollection $resources,
        public readonly Manifest $manifest,
    ) {}

    public function addResource(Resource $resource): void
    {
        $this->resources->add($resource);
        $this->manifest->addResource(ManifestResource::fromResource($resource));
    }

    /**
     * Add an assessment item to the package: register the resource in the
     * manifest and append an item ref to the assessment test section. The item
     * file's identifier attribute is normalised to the resource identifier.
     */
    public function addItem(Resource $item): void
    {
        if ($item->type !== ResourceType::ASSESSMENT_ITEM || $item->href === null) {
            throw new InvalidArgumentException('Only assessment item resources with an href can be added as item');
        }

        $this->normaliseItemIdentifier($item);
        $this->addResource($item);
        $this->getAssessmentTestFile()->addItemRef($item->identifier, $item->href);
    }

    /**
     * Replace the content of an existing assessment item. Manifest and
     * assessment test stay as they are.
     */
    public function updateItem(string $identifier, string $itemXml): Resource
    {
        $item = $this->getItemResource($identifier);

        $this->getItemFile($item)->replaceContent($itemXml);
        $this->normaliseItemIdentifier($item);

        return $item;
    }

    /**
     * Reorder the item refs of the assessment test section to match the given order.
     * @param list<string> $orderedIdentifiers
     */
    public function reorderItems(array $orderedIdentifiers): void
    {
        $this->getAssessmentTestFile()->reorderItemRefs($orderedIdentifiers);
    }

    /**
     * @return list<string>
     */
    public function getItemIdentifiers(): array
    {
        return array_map(
            static fn(Resource $resource): string => $resource->identifier,
            $this->resources->filterByType(ResourceType::ASSESSMENT_ITEM)->all(),
        );
    }

    public function getItemResource(string $identifier): Resource
    {
        $item = $this->resources
            ->filterByType(ResourceType::ASSESSMENT_ITEM)
            ->filter(static fn(Resource $resource): bool => $resource->identifier === $identifier)
            ->first();

        if (!$item instanceof Resource) {
            throw new ResourceNotFoundException('AssessmentItem', $identifier);
        }

        return $item;
    }

    public function getAssessmentTestFile(): AssessmentTestFile
    {
        $testResource = $this->resources->filterByType(ResourceType::ASSESSMENT_TEST)->first();
        if (!$testResource instanceof Resource) {
            throw new InvalidQtiPackageException(new StringCollection(['Package has no assessment test resource']));
        }

        $testFile = $testResource->getMainFile();
        if (!$testFile instanceof AssessmentTestFile) {
            throw new InvalidQtiPackageException(new StringCollection(['Assessment test resource has no assessment test file']));
        }

        return $testFile;
    }

    private function getItemFile(Resource $item): XmlFile
    {
        $itemFile = $item->getMainFile();
        if (!$itemFile instanceof XmlFile) {
            throw new InvalidQtiPackageException(new StringCollection([sprintf('Item %s has no XML file', $item->identifier)]));
        }

        return $itemFile;
    }

    private function normaliseItemIdentifier(Resource $item): void
    {
        $this->getItemFile($item)->getDocumentElement()->setAttribute('identifier', $item->identifier);
    }

    public function getFiles(): PackageFileCollection
    {
        $files = new PackageFileCollection();
        foreach ($this->resources as $resource) {
            foreach ($resource->files as $file) {
                $files->add($file);
            }
        }
        $files->add($this->manifest);
        return $files;
    }

    public function getAssessmentTestIdentifier(): string
    {
        /**
         * @var Resource $assessmentTestFile
         */
        $assessmentTestFile = $this->resources->filterByType(ResourceType::ASSESSMENT_TEST)->first();

        return $assessmentTestFile->identifier;
    }

    public function getMetadata(): ?Metadata
    {
        /**
         * @var Resource|null $assessmentTestFile
         */
        $assessmentTestFile = $this->resources->filterByType(ResourceType::ASSESSMENT_TEST)->first();

        return $assessmentTestFile?->metadata;
    }

    public function getFile(string $itemFilepath): PackageFile
    {
        /** @var PackageFile $file */
        foreach ($this->getFiles() as $file) {
            if ($file->getFilepath() === $itemFilepath) {
                return $file;
            }
        }

        throw new ResourceNotFoundException(PackageFile::class, $itemFilepath);
    }

    public function hasFile(string $itemFilepath): bool
    {
        try {
            $this->getFile($itemFilepath);
            return true;
        } catch (ResourceNotFoundException) {
            return false;
        }
    }
}
