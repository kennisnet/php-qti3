<?php

declare(strict_types=1);

namespace Qti3\Package\Model;

use Qti3\Shared\Exception\ResourceNotFoundException;
use Qti3\Package\Model\Manifest\Manifest;
use Qti3\Package\Model\Manifest\ManifestResource;
use Qti3\Package\Model\Metadata\Metadata;
use Qti3\Package\Model\PackageFile\PackageFile;
use Qti3\Package\Model\PackageFile\PackageFileCollection;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Model\Resource\ResourceCollection;
use Qti3\Package\Model\Resource\ResourceType;

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
     * @throws ResourceNotFoundException when no matching resource exists
     */
    public function getResource(string $identifier, ?ResourceType $type = null): Resource
    {
        foreach ($this->resources as $resource) {
            if ($resource->identifier === $identifier && ($type === null || $resource->type === $type)) {
                return $resource;
            }
        }

        throw new ResourceNotFoundException(Resource::class, $identifier);
    }

    /**
     * @throws ResourceNotFoundException when no such resource exists
     */
    public function removeResource(string $identifier): void
    {
        $this->resources->remove($this->getResource($identifier));
        $this->manifest->removeResource($identifier);
    }

    public function hasResource(string $identifier, ?ResourceType $type = null): bool
    {
        try {
            $this->getResource($identifier, $type);
            return true;
        } catch (ResourceNotFoundException) {
            return false;
        }
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

    public function getFile(string $filepath): PackageFile
    {
        /** @var PackageFile $file */
        foreach ($this->getFiles() as $file) {
            if ($file->getFilepath() === $filepath) {
                return $file;
            }
        }

        throw new ResourceNotFoundException(PackageFile::class, $filepath);
    }

    public function hasFile(string $filepath): bool
    {
        try {
            $this->getFile($filepath);
            return true;
        } catch (ResourceNotFoundException) {
            return false;
        }
    }
}
