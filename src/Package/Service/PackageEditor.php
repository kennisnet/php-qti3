<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

use DOMElement;
use InvalidArgumentException;
use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\AssessmentItem\Model\AssessmentItemId;
use Qti3\AssessmentItem\Service\ItemIdentifierGenerator;
use Qti3\AssessmentItem\Service\Parser\AssessmentItemParser;
use Qti3\AssessmentItem\Service\Parser\ParseError;
use Qti3\AssessmentTest\Exception\InvalidAssessmentTestException;
use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use Qti3\AssessmentTest\Service\TestBuilder;
use Qti3\AssessmentTest\Service\TestParseResult;
use Qti3\Package\Exception\InvalidQtiPackageException;
use Qti3\Package\Model\FileContent\MemoryFileContent;
use Qti3\Package\Model\Manifest\ManifestResourceDependency;
use Qti3\Package\Model\Manifest\ManifestResourceDependencyCollection;
use Qti3\Package\Model\PackageFile\XmlFile;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Model\Resource\ResourceType;
use Qti3\Package\Model\Resource\Webcontent;
use Qti3\Package\Service\QtiPackageBuilder\ItemResourceBuilder;
use Qti3\Package\Service\QtiPackageBuilder\TestResourceBuilder;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Exception\ResourceNotFoundException;
use ValueError;

/**
 * Edits the assessment items of a {@see QtiPackage} in place, without doing any
 * filesystem I/O: the caller loads the package and saves it afterwards.
 *
 * Every operation is surgical — it touches only the test it must (selected by
 * `$testId`, so multi-test packages are supported) and the single item added or
 * updated; everything else is left untouched. Items are passed as typed
 * {@see AssessmentItem} models and carry their own identifier (see
 * {@see self::getAvailableItemIdentifier()}).
 */
final readonly class PackageEditor
{
    public function __construct(
        private TestBuilder $testBuilder,
        private ItemIdentifierGenerator $identifierGenerator,
        private TestResourceBuilder $testResourceBuilder,
        private ItemResourceBuilder $itemResourceBuilder,
        private WebcontentProcessor $webcontentProcessor,
        private AssessmentItemParser $assessmentItemParser,
        private WebcontentIdentifierGenerator $webcontentIdentifierGenerator,
    ) {}

    /** Next free item identifier for the package (`ITEMnnn`, package-unique). */
    public function getAvailableItemIdentifier(QtiPackage $package): string
    {
        return $this->identifierGenerator->nextIdentifier($this->itemIdentifiers($package));
    }

    /**
     * Add a standalone webcontent asset (e.g. an uploaded image) to the package,
     * independent of any item. The caller supplies the package-relative `$path`
     * where the file should live (e.g. `resources/pic.png`); intermediate
     * directories in that path are created by the writer when the package is
     * saved. The file is registered as a `webcontent` resource with a fresh
     * `RESOURCEnnn` identifier.
     *
     * A later {@see self::updateItem()} whose item XML references `$path` reuses
     * this resource rather than re-adding it. Throws when `$path` is absolute or
     * escapes the package (`..`), or when the package already holds a file at
     * `$path`.
     */
    public function addResource(QtiPackage $package, string $path, string $content, bool $isBinary = true): EditResult
    {
        $this->assertValidResourcePath($path);
        if ($this->resourceByHref($package, $path) !== null) {
            throw new InvalidArgumentException(sprintf('Package already contains a resource at path "%s"', $path));
        }

        $resource = new Webcontent(
            $path,
            $this->webcontentIdentifierGenerator->nextIdentifier($this->resourceIdentifiers($package)),
            $path,
            new MemoryFileContent($content),
            $isBinary,
        );
        $package->addResource($resource);

        return new EditResult($resource, new StringCollection());
    }

    /**
     * Add an item to the test `$testId`. By default the item is given the next
     * free identifier ({@see self::getAvailableItemIdentifier()}); pass
     * `$identifier` to assign one yourself. Position -1 appends; a zero-based
     * position inserts at that index in the section. The result carries any
     * warnings raised while re-parsing the test being edited.
     */
    public function addItemToTest(QtiPackage $package, string $testId, AssessmentItem $item, ?string $identifier = null, int $position = -1): EditResult
    {
        $testResource = $package->getResource($testId, ResourceType::ASSESSMENT_TEST);
        $parsed = $this->buildTest($package, $testId);
        $test = $parsed->test;

        $identifier ??= $this->getAvailableItemIdentifier($package);
        $this->assertIdentifierAvailable($package, $identifier);
        $item = $item->withIdentifier(AssessmentItemId::fromString($identifier));

        // Media-resolution warnings join the test-parse warnings: both surface
        // data loss the caller must see (a refused reference leaves a dangling
        // src in the regenerated item).
        [$dependencies, $newWebcontent] = $this->webcontentProcessor->resolveNewWebcontent($package, $item, $parsed->warnings);
        $itemResource = $this->itemResourceBuilder->build($identifier, $item, $dependencies, $identifier . '.xml');

        $test->addItemRef(new AssessmentItemRef($item->identifier(), $identifier . '.xml'), $position);
        $this->rewriteTestXml($test, $testResource);

        $package->addResource($itemResource);
        $this->registerWebcontent($package, $newWebcontent);
        $package->manifest->addDependency($testResource->identifier, $identifier);
        $testResource->resourceDependencies->add(new ManifestResourceDependency($identifier));

        return new EditResult($itemResource, $parsed->warnings);
    }

    /**
     * Replace an existing item's content. Rewrites only the item resource, not
     * any test, so it works even when the surrounding test uses unsupported
     * constructs. (Warnings about the new item's own content, if any, come from
     * parsing it — see {@see \Qti3\AssessmentItem\Service\Parser\ItemParseResult}.)
     */
    public function updateItem(QtiPackage $package, AssessmentItem $item): EditResult
    {
        $identifier = (string) $item->identifier();
        $itemResource = $package->getResource($identifier, ResourceType::ASSESSMENT_ITEM);

        // Which media the item referenced *before* this edit, derived from the
        // current content by the same scan that produces the new dependencies.
        // A dependency to a non-media resource (e.g. a metadata resource) never
        // shows up here, so the reconcile below leaves it untouched.
        $previousMediaDependencies = $this->mediaDependenciesOf($package, $itemResource);

        // Collect media-resolution warnings so they reach the EditResult: a
        // refused reference is dropped from the package while the regenerated
        // item XML keeps its original src, which is data loss the caller must see.
        $warnings = new StringCollection();
        [$dependencies, $newWebcontent] = $this->webcontentProcessor->resolveNewWebcontent($package, $item, $warnings);

        $rebuilt = $this->itemResourceBuilder->build($identifier, $item, $dependencies, $itemResource->href);
        $this->getXmlFileFromResource($itemResource)->replaceContent((string) $rebuilt->getMainFile());

        $this->registerWebcontent($package, $newWebcontent);
        $this->reconcileMediaDependencies($package, $itemResource, $previousMediaDependencies, $dependencies);

        return new EditResult($itemResource, $warnings);
    }

    /**
     * Remove an item from the test `$testId`: drop its item ref and rewrite the
     * test. The item resource and its file are removed too, unless another test
     * still references them. Any media the item introduced is left in place.
     */
    public function removeItemFromTest(QtiPackage $package, string $testId, string $identifier): EditResult
    {
        $testResource = $package->getResource($testId, ResourceType::ASSESSMENT_TEST);
        $parsed = $this->buildTest($package, $testId);
        $test = $parsed->test;

        $itemId = AssessmentItemId::fromString($identifier);
        if (!$this->testContainsItem($test, $itemId)) {
            throw new ResourceNotFoundException(AssessmentItem::class, $identifier);
        }

        $test->removeItemRef($itemId);
        $this->rewriteTestXml($test, $testResource);

        $package->manifest->removeDependency($testResource->identifier, $identifier);
        $this->unlinkDependency($testResource, $identifier);

        if (!$this->itemReferencedByOtherTest($package, $identifier, $testId)) {
            $package->removeResource($identifier);
        }

        return new EditResult(null, $parsed->warnings);
    }

    /** @param list<string> $orderedIdentifiers */
    public function reorderItemsInTest(QtiPackage $package, string $testId, array $orderedIdentifiers): EditResult
    {
        $testResource = $package->getResource($testId, ResourceType::ASSESSMENT_TEST);
        $parsed = $this->buildTest($package, $testId);
        $test = $parsed->test;

        $test->reorderItemRefs($orderedIdentifiers);

        $this->rewriteTestXml($test, $testResource);

        return new EditResult(null, $parsed->warnings);
    }

    private function buildTest(QtiPackage $package, string $testId): TestParseResult
    {
        try {
            return $this->testBuilder->buildFromPackage($package, $testId);
        } catch (ParseError | InvalidArgumentException | ValueError $exception) {
            throw new InvalidAssessmentTestException(new StringCollection(['Failed to parse assessment test: ' . $exception->getMessage()]));
        }
    }

    private function rewriteTestXml(AssessmentTest $test, Resource $testResource): void
    {
        // Dependencies do not affect the generated test XML, only the manifest,
        // which is kept in sync separately, so an empty collection is fine here.
        $rebuilt = $this->testResourceBuilder->build(
            $test,
            new ManifestResourceDependencyCollection(),
            $testResource->identifier,
            (string) $testResource->href,
        );

        $this->getXmlFileFromResource($testResource)->replaceContent((string) $rebuilt->getMainFile());
    }

    /**
     * @param list<Webcontent> $webcontent
     */
    private function registerWebcontent(QtiPackage $package, array $webcontent): void
    {
        foreach ($webcontent as $webcontentFile) {
            $package->addResource($webcontentFile);
        }
    }

    /**
     * A resource path must stay inside the package: no absolute path and no
     * `..` segment that would let a write escape the package directory.
     */
    private function assertValidResourcePath(string $path): void
    {
        if ($path === ''
            || str_starts_with($path, '/')
            || preg_match('~(^|/)\.\.(/|$)~', $path) === 1
        ) {
            throw new InvalidArgumentException(sprintf('Invalid resource path "%s": must be a relative path inside the package', $path));
        }
    }

    /**
     * @return list<string>
     */
    private function resourceIdentifiers(QtiPackage $package): array
    {
        return array_map(
            static fn(Resource $resource): string => $resource->identifier,
            $package->resources->all(),
        );
    }

    private function resourceByHref(QtiPackage $package, string $href): ?Resource
    {
        foreach ($package->resources as $resource) {
            if ($resource->href === $href) {
                return $resource;
            }
        }

        return null;
    }

    private function linkNewDependencies(QtiPackage $package, Resource $itemResource, ManifestResourceDependencyCollection $dependencies): void
    {
        foreach ($dependencies as $dependency) {
            if ($this->hasDependency($itemResource, $dependency->identifierref)) {
                continue;
            }
            $package->manifest->addDependency($itemResource->identifier, $dependency->identifierref);
            $itemResource->resourceDependencies->add($dependency);
        }
    }

    /**
     * Sync the item's media dependencies to the rebuilt content: retire the ones
     * the item no longer references and link the ones it now does. Only media
     * dependencies are considered on the removal side (those the content scan
     * produced for the previous item), so a dependency to a non-media resource
     * such as a metadata resource is never removed.
     */
    private function reconcileMediaDependencies(
        QtiPackage $package,
        Resource $itemResource,
        ManifestResourceDependencyCollection $previous,
        ManifestResourceDependencyCollection $current,
    ): void {
        $currentRefs = $this->identifierRefs($current);
        foreach ($this->identifierRefs($previous) as $ref) {
            if (!in_array($ref, $currentRefs, true)) {
                $package->manifest->removeDependency($itemResource->identifier, $ref);
                $this->unlinkDependency($itemResource, $ref);
            }
        }

        $this->linkNewDependencies($package, $itemResource, $current);
    }

    /**
     * The media dependencies of a resource's current content, resolved against
     * the package so their identifiers match the package's own resources.
     * Returns an empty collection when the content cannot be parsed, degrading
     * to add-only linking rather than removing dependencies on a guess.
     */
    private function mediaDependenciesOf(QtiPackage $package, Resource $itemResource): ManifestResourceDependencyCollection
    {
        try {
            $item = $this->assessmentItemParser->parse($this->getXmlFileFromResource($itemResource)->getDocumentElement())->item;
        } catch (ParseError | InvalidArgumentException | ValueError) {
            return new ManifestResourceDependencyCollection();
        }

        // Re-scanning the *previous* content only to diff dependencies; its
        // warnings were already surfaced when that content was added, so they
        // are discarded here.
        [$dependencies] = $this->webcontentProcessor->resolveNewWebcontent($package, $item, new StringCollection());

        return $dependencies;
    }

    /**
     * @return list<string>
     */
    private function identifierRefs(ManifestResourceDependencyCollection $dependencies): array
    {
        return array_map(
            static fn(ManifestResourceDependency $dependency): string => $dependency->identifierref,
            $dependencies->all(),
        );
    }

    /**
     * @return list<string>
     */
    private function itemIdentifiers(QtiPackage $package): array
    {
        return array_map(
            static fn(Resource $resource): string => $resource->identifier,
            $package->resources->filterByType(ResourceType::ASSESSMENT_ITEM)->all(),
        );
    }

    private function assertIdentifierAvailable(QtiPackage $package, string $identifier): void
    {
        if ($package->hasResource($identifier)) {
            throw new InvalidAssessmentTestException(new StringCollection([sprintf('Package already contains a resource with identifier "%s"', $identifier)]));
        }
    }

    private function hasDependency(Resource $resource, string $dependencyRef): bool
    {
        foreach ($resource->resourceDependencies as $dependency) {
            if ($dependency->identifierref === $dependencyRef) {
                return true;
            }
        }

        return false;
    }

    private function testContainsItem(AssessmentTest $test, AssessmentItemId $itemId): bool
    {
        foreach ($test->getItemRefs() as $itemRef) {
            if ($itemRef->identifier->equals($itemId)) {
                return true;
            }
        }

        return false;
    }

    private function unlinkDependency(Resource $resource, string $dependencyRef): void
    {
        $resource->resourceDependencies->replaceAll(array_values(array_filter(
            $resource->resourceDependencies->all(),
            static fn(ManifestResourceDependency $dependency): bool => $dependency->identifierref !== $dependencyRef,
        )));
    }

    /**
     * Read the item refs of the other tests straight from their XML (not through
     * the model, to avoid the support check), so a shared item is never removed
     * while another test still references it.
     */
    private function itemReferencedByOtherTest(QtiPackage $package, string $identifier, string $exceptTestId): bool
    {
        foreach ($package->resources->filterByType(ResourceType::ASSESSMENT_TEST) as $testResource) {
            if ($testResource->identifier === $exceptTestId) {
                continue;
            }
            $file = $testResource->getMainFile();
            if (!$file instanceof XmlFile) {
                continue;
            }
            foreach ($file->getDocumentElement()->getElementsByTagNameNS('*', 'qti-assessment-item-ref') as $itemRef) {
                if ($itemRef instanceof DOMElement && $itemRef->getAttribute('identifier') === $identifier) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getXmlFileFromResource(Resource $resource): XmlFile
    {
        $file = $resource->getMainFile();
        if (!$file instanceof XmlFile) {
            throw new InvalidQtiPackageException(new StringCollection([sprintf('Resource "%s" has no XML file', $resource->identifier)]));
        }

        return $file;
    }
}
