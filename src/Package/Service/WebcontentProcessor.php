<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

use Qti3\Package\Model\FileContent\ExternalFileContent;
use Qti3\Package\Model\FileContent\IFileContent;
use Qti3\Package\Model\FileContent\LocalFileContent;
use Qti3\Package\Model\IMediaSource;
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
 * Resolves the external files (images, audio, stylesheets, ...) referenced by a
 * QTI element into {@see Webcontent} resources and their manifest dependencies.
 * A file already in the package is carried over as-is; a new reference is
 * validated and, when needed, downloaded.
 */
final readonly class WebcontentProcessor
{
    public function __construct(
        private IResourceValidator $resourceValidator,
        private IResourceDownloader $resourceDownloader,
        private WebcontentIdentifierGenerator $identifierGenerator,
    ) {}

    /**
     * Collect the webcontent referenced by `$element` into `$webcontent` and
     * return the dependencies on them. Entries already in `$webcontent` (matched
     * by original path) are reused.
     *
     * Pass `$packageMediaSource` (the media files of the package's own source,
     * addressed by package-relative path) to accept local file references for
     * this operation; without it every local path is refused.
     */
    public function process(
        WebcontentCollection $webcontent,
        IXmlElement $element,
        StringCollection $warnings,
        ?QtiPackage $sourcePackage,
        ?IMediaSource $packageMediaSource = null,
    ): ManifestResourceDependencyCollection {
        $qtiResources = $this->getQtiResources($element, $warnings, $sourcePackage, $packageMediaSource);

        $dependencies = new ManifestResourceDependencyCollection();
        foreach ($qtiResources as $qtiResource) {
            $webcontentFile = $webcontent->findByOriginalPath($qtiResource->originalPath);
            if (!$webcontentFile) {
                $webcontentFile = new Webcontent(
                    $qtiResource->originalPath,
                    $this->identifierGenerator->nextIdentifier($this->usedIdentifiers($webcontent, $sourcePackage)),
                    $qtiResource->relativePath . $qtiResource->filename,
                    $this->contentFor($qtiResource->originalPath, $sourcePackage, $packageMediaSource),
                    $qtiResource->isBinary,
                );
                $webcontent->add($webcontentFile);
            }
            $dependencies->add(new ManifestResourceDependency($webcontentFile->identifier));
        }
        return $dependencies;
    }

    /**
     * Resolve the media of a single item against an existing package: return the
     * dependencies for the item's resource plus the webcontent that is new. A
     * file whose derived path already exists is reused (its dependency remapped)
     * instead of duplicated — e.g. shared media or the item stylesheet.
     *
     * Warnings raised while resolving the media (e.g. a refused local path or
     * an unreachable URL) are collected into `$warnings` so the caller can
     * surface them: such a reference is dropped from the package while the
     * regenerated item XML keeps its original `src`, which is data loss.
     *
     * @return array{0: ManifestResourceDependencyCollection, 1: list<Webcontent>}
     */
    public function resolveNewWebcontent(
        QtiPackage $package,
        IXmlElement $element,
        StringCollection $warnings,
        ?IMediaSource $packageMediaSource = null,
    ): array {
        $webcontent = new WebcontentCollection();
        $rawDependencies = $this->process($webcontent, $element, $warnings, $package, $packageMediaSource);

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

    /**
     * Walk every resource reference in `$element` and return a message for each
     * one that cannot be resolved against `$package`. A reference is valid when
     * it is a `data:` URI, an `http(s)` URL, a trusted (library-provided) asset,
     * a file already present in the package, or a file in `$packageMediaSource`
     * (the media files of the package's own source). A relative path found in
     * none of those — or a path that escapes the package (absolute or containing
     * `..`) — is invalid. Read-only: unlike {@see self::process()} it resolves
     * nothing.
     *
     * @return list<string>
     */
    public function findInvalidReferences(IXmlElement $element, QtiPackage $package, ?IMediaSource $packageMediaSource = null): array
    {
        // The element itself may be a resource provider (many nodes are), so it
        // is checked too, not just its descendants.
        $invalid = [];
        if ($element instanceof IQtiResourceProvider) {
            $message = $this->invalidReferenceMessage($element, $package, $packageMediaSource);
            if ($message !== null) {
                $invalid[] = $message;
            }
        }

        return [...$invalid, ...$this->invalidReferencesInDescendants($element, $package, $packageMediaSource)];
    }

    /**
     * @return list<string>
     */
    private function invalidReferencesInDescendants(IXmlElement $element, QtiPackage $package, ?IMediaSource $packageMediaSource): array
    {
        $invalid = [];
        foreach ($element->children() as $child) {
            if ($child instanceof IQtiResourceProvider) {
                $message = $this->invalidReferenceMessage($child, $package, $packageMediaSource);
                if ($message !== null) {
                    $invalid[] = $message;
                }
            }
            if ($child instanceof IXmlElement) {
                $invalid = [...$invalid, ...$this->invalidReferencesInDescendants($child, $package, $packageMediaSource)];
            }
        }

        return $invalid;
    }

    private function invalidReferenceMessage(IQtiResourceProvider $provider, QtiPackage $package, ?IMediaSource $packageMediaSource): ?string
    {
        $source = $provider->getSource();
        if ($source === null || $source === '' || str_starts_with($source, 'data:')) {
            return null;
        }
        if ($package->hasFile($source)
            || preg_match('~^https?://~i', $source) === 1
            || $provider->isTrustedSource()
            || ($packageMediaSource?->hasFile($source) ?? false)
        ) {
            return null;
        }

        if (str_starts_with($source, '/') || preg_match('~(^|/)\.\.(/|$)~', $source) === 1) {
            return sprintf('References a path outside the package: "%s"', $source);
        }

        return sprintf('References a resource that is not present in the package: "%s"', $source);
    }

    /**
     * The identifiers already taken for the run: those of the webcontent
     * gathered so far this pass plus every resource already in the source
     * package. Feeding these to the generator keeps a new media file from being
     * handed an identifier that is already in use.
     *
     * @return list<string>
     */
    private function usedIdentifiers(WebcontentCollection $webcontent, ?QtiPackage $sourcePackage): array
    {
        $identifiers = [];
        foreach ($webcontent as $existing) {
            $identifiers[] = $existing->identifier;
        }
        foreach ($sourcePackage?->resources ?? [] as $resource) {
            $identifiers[] = $resource->identifier;
        }

        return $identifiers;
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
    private function getQtiResources(
        IXmlElement $element,
        StringCollection $warnings,
        ?QtiPackage $sourcePackage,
        ?IMediaSource $packageMediaSource,
    ): array {
        $resources = [];

        foreach ($element->children() as $child) {
            if ($child instanceof IQtiResourceProvider) {
                $this->processResourceProvider($child, $warnings, $sourcePackage, $packageMediaSource);
                $resource = $child->getResource();
                if ($resource !== null) {
                    $resources[] = $resource;
                }
            }
            if ($child instanceof IXmlElement) {
                $resources = [...$resources, ...$this->getQtiResources($child, $warnings, $sourcePackage, $packageMediaSource)];
            }
        }

        return $resources;
    }

    private function processResourceProvider(
        IQtiResourceProvider $resourceProvider,
        StringCollection $warnings,
        ?QtiPackage $sourcePackage,
        ?IMediaSource $packageMediaSource,
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

        $resource = new QtiResource(
            type: 'webcontent',
            originalPath: $source,
            relativePath: 'resources/',
            filename: md5($source) . '.' . pathinfo($source, PATHINFO_EXTENSION),
            isBinary: $resourceProvider->isBinary(),
        );

        // A remote reference is validated before it is accepted (and later
        // downloaded).
        if (preg_match('~^https?://~i', $source) === 1) {
            try {
                $this->resourceValidator->validate($resource);
                $resourceProvider->setResource($resource);
            } catch (Exception $e) {
                $warnings->add($e->getMessage());
            }
            return;
        }

        // Any other source is a local path. Only a library-provided (trusted)
        // asset such as the default stylesheet, or a file in the media source
        // of the package being built, may be read; any other local path coming
        // from item content is refused so it can never read an arbitrary file
        // (e.g. "/etc/passwd" or "../secret") into the package.
        if (!$resourceProvider->isTrustedSource() && !($packageMediaSource?->hasFile($source) ?? false)) {
            $warnings->add(sprintf('Refused local file reference "%s": only in-package files, package media, data URIs and http(s) URLs are allowed', $source));
            return;
        }

        $resourceProvider->setResource($resource);
    }

    private function contentFor(string $originalPath, ?QtiPackage $sourcePackage, ?IMediaSource $packageMediaSource): IFileContent
    {
        if ($sourcePackage?->hasFile($originalPath)) {
            return $sourcePackage->getFile($originalPath)->getContent();
        }
        if (preg_match('~^https?://~i', $originalPath) === 1) {
            return new ExternalFileContent($originalPath, $this->resourceDownloader);
        }
        if ($packageMediaSource?->hasFile($originalPath)) {
            return $packageMediaSource->getFileContent($originalPath);
        }

        return new LocalFileContent($originalPath);
    }

    private function relativeDirectory(string $path): string
    {
        $directory = dirname($path);

        return $directory === '.' ? '' : $directory . '/';
    }
}
