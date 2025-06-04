<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service;

use App\SharedKernel\Domain\Qti\Package\IQtiPackageFactory;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\XmlFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\IPackageReader;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\IManifestFactory;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\Manifest;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResource;
use App\SharedKernel\Domain\Qti\Package\Model\Metadata\Metadata;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
use App\SharedKernel\Domain\Qti\Shared\Xml\Reader\IXmlReader;

readonly class QtiPackageReader implements IQtiPackageFactory
{
    public function __construct(
        private IManifestFactory $manifestFactory,
        private IXmlReader $xmlReader,
        private IZipPackageFactory $zipPackageFactory,
        private IFilesystemPackageFactory $filesystemPackageFactory,
    ) {}

    public function fromFilesystem(string $folder): QtiPackage
    {
        $reader = $this->filesystemPackageFactory->getReader($folder);

        return $this->fromReader($reader);
    }

    public function fromZip(string $filePath): QtiPackage
    {
        $reader = $this->zipPackageFactory->getReader($filePath);

        return $this->fromReader($reader);
    }

    private function fromReader(IPackageReader $reader): QtiPackage
    {
        $resources = new ResourceCollection();

        $manifest = $this->manifestFactory->createFromXmlString($reader->readFile('imsmanifest.xml'));

        foreach ($manifest->getResources() as $manifestResource) {
            $files = new ResourceFileCollection();
            foreach ($manifestResource->files as $manifestFile) {
                $extension = pathinfo($manifestFile->href, PATHINFO_EXTENSION);
                if ($extension === 'xml') {
                    $fileContent = XmlFileContent::fromString($reader->readFile($manifestFile->href), $this->xmlReader);
                } else {
                    $fileContent = new MemoryFileContent($reader->readFile($manifestFile->href));
                }

                $files->add(new ResourceFile(
                    $manifestFile->href,
                    $fileContent
                ));
            }
            $resources[] = new Resource(
                $manifestResource->identifier,
                $manifestResource->type,
                $manifestResource->href,
                $files,
                $manifestResource->dependencies,
                $this->determineMetadata($manifestResource, $manifest, $reader, $this->xmlReader),
            );

        }

        return new QtiPackage(
            $resources,
            $manifest,
        );
    }

    private function determineMetadata(ManifestResource $resource, Manifest $manifest, IPackageReader $reader, IXmlReader $xmlReader): ?Metadata
    {
        foreach ($resource->dependencies as $dependency) {
            $metadataResources = $manifest->getResources()->filter(
                fn(ManifestResource $manifestResource): bool =>
                    $manifestResource->identifier === $dependency->identifierref &&
                    $manifestResource->type === ResourceType::RESOURCE_METADATA
            );

            if ($metadataResources->count() === 0) {
                continue;
            }

            /** @var ManifestResource $metadataResource */
            $metadataResource = $metadataResources->first();

            // Metadata resource always contains a href
            /** @var string $href */
            $href = $metadataResource->href;

            $fileContent = XmlFileContent::fromString($reader->readFile($href), $xmlReader);

            return new Metadata($fileContent->xmlDocument);
        }

        return null;
    }
}
