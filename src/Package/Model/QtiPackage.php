<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model;

use App\SharedKernel\Domain\Qti\Package\Model\Manifest\Manifest;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResource;
use App\SharedKernel\Domain\Qti\Package\Model\Metadata\Metadata;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;

class QtiPackage
{
    public function __construct(
        public readonly ResourceCollection $resources,
        public readonly Manifest $manifest,
    ) {}

    public function addResource(Resource $resource): void
    {
        $this->resources->add($resource);
        $this->manifest->addResource(ManifestResource::fromResourceFile($resource));
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
}
