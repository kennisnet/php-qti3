<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

use InvalidArgumentException;
use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\AssessmentItem\Service\ItemIdentifierGenerator;
use Qti3\AssessmentItem\Service\Parser\ParseError;
use Qti3\AssessmentTest\Exception\InvalidAssessmentTestException;
use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use Qti3\AssessmentTest\Service\TestBuilder;
use Qti3\Package\Exception\InvalidQtiPackageException;
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
use ValueError;

/**
 * Domain service that edits the assessment items of a QTI package in place.
 *
 * Items are handed in as typed {@see AssessmentItem} models — the caller builds
 * or parses them (e.g. through {@see \Qti3\QtiClient::getAssessmentItemParser()})
 * and owns their identifiers; {@see self::getAvailableItemIdentifier()} vends a
 * free one. Because a model is by definition representable, no XML validation
 * or support check happens here for items.
 *
 * Every operation is surgical: it touches only the resource(s) it must. Adding
 * or reordering rewrites a single assessment test (named by `$testId`, so
 * packages with more than one test are supported) and, for an add, appends one
 * item resource. Updating replaces a single item resource. Untouched items,
 * media and metadata are left exactly as they are.
 *
 * The service does no filesystem I/O: the caller loads the {@see QtiPackage}
 * and, after the edit, saves it through a package writer. The passed package is
 * mutated directly; add and update return the affected {@see Resource}.
 *
 * A test that is rewritten (add, reorder) must stay within the subset the
 * {@see AssessmentTest} model can represent; {@see TestBuilder} refuses the rest
 * with {@see \Qti3\Shared\Exception\UnsupportedQtiConstructException}. Updating
 * an item does not rewrite the test, so it is not bound by that constraint.
 */
final readonly class PackageEditor
{
    public function __construct(
        private TestBuilder $testBuilder,
        private ItemIdentifierGenerator $identifierGenerator,
        private TestResourceBuilder $testResourceBuilder,
        private ItemResourceBuilder $itemResourceBuilder,
        private WebcontentProcessor $webcontentProcessor,
    ) {}

    /**
     * Return the next free item identifier for the package (`ITEMnnn`, unique
     * across the package). Use it to stamp a self-built item before adding it.
     */
    public function getAvailableItemIdentifier(QtiPackage $package): string
    {
        return $this->identifierGenerator->nextIdentifier($this->itemIdentifiers($package));
    }

    /**
     * Add an item to the test identified by `$testId`, using the item's own
     * identifier. With the default position (-1) it is appended to the section;
     * a zero-based position inserts it at that index. Returns the added item
     * resource.
     */
    public function addItemToTest(QtiPackage $package, string $testId, AssessmentItem $item, int $position = -1): Resource
    {
        $testResource = $package->getResource($testId, ResourceType::ASSESSMENT_TEST);
        $test = $this->buildTest($package, $testId);

        $identifier = (string) $item->identifier();
        $this->assertIdentifierAvailable($package, $identifier);

        [$dependencies, $newWebcontent] = $this->webcontentProcessor->resolveNewWebcontent($package, $item);
        $itemResource = $this->itemResourceBuilder->build($identifier, $item, $dependencies, $identifier . '.xml');

        $test->addItemRef(new AssessmentItemRef($item->identifier(), $identifier . '.xml'), $position);
        $this->rewriteTestXml($test, $testResource);

        $package->addResource($itemResource);
        $this->registerWebcontent($package, $newWebcontent);
        $package->manifest->addDependency($testResource->identifier, $identifier);
        $testResource->resourceDependencies->add(new ManifestResourceDependency($identifier));

        return $itemResource;
    }

    /**
     * Replace the content of an existing item, identified by the item's own
     * identifier. This rewrites only the item resource, not any test, so it is
     * allowed even when the surrounding test uses unsupported constructs.
     * Returns the updated item resource.
     */
    public function updateItem(QtiPackage $package, AssessmentItem $item): Resource
    {
        $identifier = (string) $item->identifier();
        $itemResource = $package->getResource($identifier, ResourceType::ASSESSMENT_ITEM);

        [$dependencies, $newWebcontent] = $this->webcontentProcessor->resolveNewWebcontent($package, $item);

        $rebuilt = $this->itemResourceBuilder->build($identifier, $item, $dependencies, $itemResource->href);
        $this->getXmlFileFromResource($itemResource)->replaceContent((string) $rebuilt->getMainFile());

        $this->registerWebcontent($package, $newWebcontent);
        $this->linkNewDependencies($package, $itemResource, $dependencies);

        return $itemResource;
    }

    /**
     * Rewrite the item order of the section of the test identified by `$testId`.
     *
     * @param list<string> $orderedIdentifiers
     */
    public function reorderItemsInTest(QtiPackage $package, string $testId, array $orderedIdentifiers): void
    {
        $testResource = $package->getResource($testId, ResourceType::ASSESSMENT_TEST);
        $test = $this->buildTest($package, $testId);

        $test->reorderItemRefs($orderedIdentifiers);

        $this->rewriteTestXml($test, $testResource);
    }

    private function buildTest(QtiPackage $package, string $testId): AssessmentTest
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

    private function getXmlFileFromResource(Resource $resource): XmlFile
    {
        $file = $resource->getMainFile();
        if (!$file instanceof XmlFile) {
            throw new InvalidQtiPackageException(new StringCollection([sprintf('Resource "%s" has no XML file', $resource->identifier)]));
        }

        return $file;
    }
}
