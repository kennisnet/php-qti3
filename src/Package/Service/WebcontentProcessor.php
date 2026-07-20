<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

use Qti3\Package\Model\FileContent\ExternalFileContent;
use Qti3\Package\Model\FileContent\IFileContent;
use Qti3\Package\Model\FileContent\LocalFileContent;
use Qti3\Package\Model\Manifest\ManifestResourceDependency;
use Qti3\Package\Model\Manifest\ManifestResourceDependencyCollection;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Model\Resource\Webcontent;
use Qti3\Package\Model\Resource\WebcontentCollection;
use Qti3\Package\Downloader\Resource\IResourceDownloader;
use Qti3\Package\Validator\Resource\IResourceValidator;
use Qti3\Shared\Model\IQtiResourceProvider;
use Qti3\Shared\Model\IXmlElement;
use Qti3\Shared\Model\QtiResource;
use Qti3\Shared\Collection\StringCollection;
use Exception;

/**
 * Resolves the external files (images, audio, ...) referenced by a QTI element
 * into {@see Webcontent} resources and the manifest dependencies pointing at
 * them. A file that already lives in the package being edited is carried over
 * as-is (path and bytes kept); a brand new reference is validated and, when
 * needed, downloaded.
 *
 * Extracted from {@see QtiPackageBuilder} so the same logic can be reused by
 * {@see PackageEditor} when a single added or updated item introduces media.
 */
final readonly class WebcontentProcessor
{
    public function __construct(
        private IResourceValidator $resourceValidator,
        private IResourceDownloader $resourceDownloader,
    ) {}

    /**
     * Collect the webcontent referenced by `$element` into `$webcontent` and
     * return the dependencies on those webcontent resources. Webcontent already
     * present in `$webcontent` (matched by original path) is reused, so callers
     * can seed the collection to deduplicate across elements.
     */
    public function process(
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

    /**
     * Resolve the media of a single element (an item being added or updated)
     * against an existing package: return the dependencies to declare on that
     * element's resource plus the webcontent resources that are new to the
     * package.
     *
     * A referenced file ends up at a path derived deterministically from its
     * source. When a resource with that path already exists — media added by an
     * earlier edit, or the shared item stylesheet — the dependency is remapped
     * onto the existing resource so it is never duplicated.
     *
     * @return array{0: ManifestResourceDependencyCollection, 1: list<Webcontent>}
     */
    public function resolveNewWebcontent(QtiPackage $package, IXmlElement $element): array
    {
        $webcontent = new WebcontentCollection();
        $rawDependencies = $this->process($webcontent, $element, new StringCollection(), $package);

        $reusedIdentifiers = [];
        $newWebcontent = [];
        foreach ($webcontent as $webcontentFile) {
            $existing = $this->resourceByHref($package, $webcontentFile->href);
            if ($existing instanceof Resource) {
                $reusedIdentifiers[$webcontentFile->identifier] = $existing->identifier;
            } else {
                $newWebcontent[] = $webcontentFile;
            }
        }

        $dependencies = new ManifestResourceDependencyCollection();
        foreach ($rawDependencies as $dependency) {
            $dependencies->add(new ManifestResourceDependency($reusedIdentifiers[$dependency->identifierref] ?? $dependency->identifierref));
        }

        return [$dependencies, $newWebcontent];
    }

    private function resourceByHref(QtiPackage $package, ?string $href): ?Resource
    {
        if ($href === null) {
            return null;
        }

        return $package->resources
            ->filter(static fn(Resource $resource): bool => $resource->href === $href)
            ->first();
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
}
