<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service;

use DOMElement;
use InvalidArgumentException;
use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\AssessmentItem\Model\AssessmentItemId;
use Qti3\AssessmentItem\Service\AssessmentItemSupportValidator;
use Qti3\AssessmentItem\Service\Parser\AssessmentItemParser;
use Qti3\AssessmentItem\Service\Parser\ParseError;
use Qti3\AssessmentTest\Exception\InvalidAssessmentTestException;
use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use Qti3\AssessmentTest\Service\AssessmentTestSupportValidator;
use Qti3\AssessmentTest\Service\TestBuilder;
use Qti3\Package\Exception\InvalidQtiPackageException;
use Qti3\Shared\Exception\UnsupportedQtiConstructException;
use Qti3\AssessmentItem\Service\ItemIdentifierGenerator;
use Qti3\Package\IQtiPackageFactory;
use Qti3\AssessmentTest\Model\IItemEditor;
use Qti3\Package\Service\IFilesystemPackageFactory;
use Qti3\Package\Model\PackageFile\XmlFile;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Model\Resource\ResourceType;
use Qti3\AssessmentItem\Service\IAssessmentItemValidator;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Exception\ResourceNotFoundException;
use Qti3\Shared\Xml\Reader\IXmlReader;
use RuntimeException;
use ValueError;

/**
 * Application service for editing assessment items in an extracted package
 * folder. It only orchestrates: every edit loads the package into the typed
 * domain models ({@see AssessmentTest} + {@see AssessmentItem}), applies the
 * change on those models, and saves by generating a new package from them
 * ({@see QtiPackageBuilder::buildForTest()}) and storing it through the
 * package writer. All business rules live in the models.
 *
 * Packages containing QTI constructs the typed models cannot represent are
 * refused with {@see UnsupportedQtiConstructException} instead of silently
 * losing those constructs on regeneration.
 */
final readonly class TestItemEditor implements IItemEditor
{
    public function __construct(
        private string $folder,
        private IQtiPackageFactory $packageFactory,
        private IFilesystemPackageFactory $filesystemPackageFactory,
        private IAssessmentItemValidator $itemValidator,
        private ItemIdentifierGenerator $identifierGenerator,
        private TestBuilder $testBuilder,
        private AssessmentItemParser $itemParser,
        private AssessmentTestSupportValidator $testSupportValidator,
        private AssessmentItemSupportValidator $itemSupportValidator,
        private QtiPackageBuilder $packageBuilder,
        private IXmlReader $xmlReader,
    ) {}

    public function addItem(string $itemXml): Resource
    {
        $this->itemValidator->validate($itemXml);

        $sourcePackage = $this->loadPackage();
        $test = $this->buildTest($sourcePackage);
        $items = $this->parseItems($sourcePackage);

        $identifier = $this->identifierGenerator->nextIdentifier($test->getItemIdentifiers());
        $items[] = $this->parseNewItem($itemXml, $identifier);
        $test->addItemRef(new AssessmentItemRef(AssessmentItemId::fromString($identifier), $identifier . '.xml'));

        return $this->storeAndReturnItem($test, $items, $sourcePackage, $identifier);
    }

    public function updateItem(string $identifier, string $itemXml): Resource
    {
        $sourcePackage = $this->loadPackage();
        $test = $this->buildTest($sourcePackage);

        // Resolve the item before validating, so a missing item is reported as
        // ResourceNotFoundException even when the new XML is also invalid.
        $this->assertItemExists($test, $identifier);
        $this->itemValidator->validate($itemXml);

        // The item being replaced is never parsed: its old content does not
        // have to be representable for the update to succeed.
        $items = $this->parseItems($sourcePackage, except: $identifier);
        $items[] = $this->parseNewItem($itemXml, $identifier);

        return $this->storeAndReturnItem($test, $items, $sourcePackage, $identifier);
    }

    public function reorderItems(array $orderedIdentifiers): void
    {
        $sourcePackage = $this->loadPackage();
        $test = $this->buildTest($sourcePackage);
        $items = $this->parseItems($sourcePackage);

        $test->reorderItemRefs($orderedIdentifiers);

        $this->store($this->rebuild($test, $items, $sourcePackage));
    }

    private function loadPackage(): QtiPackage
    {
        return $this->packageFactory->fromFilesystem($this->folder);
    }

    private function buildTest(QtiPackage $package): AssessmentTest
    {
        $this->testSupportValidator->assertSupported($this->testElement($package));

        try {
            return $this->testBuilder->buildFromPackage($package);
        } catch (ParseError | InvalidArgumentException | ValueError $exception) {
            throw new InvalidAssessmentTestException(new StringCollection(['Failed to parse assessment test: ' . $exception->getMessage()]));
        }
    }

    private function testElement(QtiPackage $package): DOMElement
    {
        $testResource = $package->resources->filterByType(ResourceType::ASSESSMENT_TEST)->first();
        if (!$testResource instanceof Resource) {
            throw new InvalidQtiPackageException(new StringCollection(['Package has no assessment test resource']));
        }

        return $this->documentElement($testResource);
    }

    /**
     * Parse every assessment item of the package into its typed model.
     *
     * @return array<int, AssessmentItem>
     */
    private function parseItems(QtiPackage $package, ?string $except = null): array
    {
        $items = [];
        foreach ($package->resources->filterByType(ResourceType::ASSESSMENT_ITEM) as $itemResource) {
            if ($itemResource->identifier === $except) {
                continue;
            }
            $items[] = $this->parseItem($this->documentElement($itemResource), $itemResource->identifier);
        }

        return $items;
    }

    private function parseNewItem(string $itemXml, string $identifier): AssessmentItem
    {
        $element = $this->xmlReader->read($itemXml)->documentElement;
        if ($element === null) {
            throw new RuntimeException('Invalid item XML'); // @codeCoverageIgnore
        }
        $element->setAttribute('identifier', $identifier);

        return $this->parseItem($element, $identifier);
    }

    private function parseItem(DOMElement $element, string $identifier): AssessmentItem
    {
        $this->itemSupportValidator->assertSupported($element);

        try {
            return $this->itemParser->parse($element);
        } catch (ParseError | InvalidArgumentException $exception) {
            throw new UnsupportedQtiConstructException(new StringCollection([sprintf('Item "%s": %s', $identifier, $exception->getMessage())]));
        }
    }

    private function assertItemExists(AssessmentTest $test, string $identifier): void
    {
        try {
            $test->findItemRef(AssessmentItemId::fromString($identifier));
        } catch (RuntimeException | InvalidArgumentException) {
            throw new ResourceNotFoundException('AssessmentItem', $identifier);
        }
    }

    /**
     * @param array<int, AssessmentItem> $items
     */
    private function storeAndReturnItem(AssessmentTest $test, array $items, QtiPackage $sourcePackage, string $identifier): Resource
    {
        $package = $this->rebuild($test, $items, $sourcePackage);
        $this->store($package);

        $item = $package->resources
            ->filter(static fn(Resource $resource): bool => $resource->identifier === $identifier)
            ->first();
        if (!$item instanceof Resource) {
            throw new RuntimeException(sprintf('Item "%s" missing from the generated package', $identifier)); // @codeCoverageIgnore
        }

        return $item;
    }

    /**
     * @param array<int, AssessmentItem> $items
     */
    private function rebuild(AssessmentTest $test, array $items, QtiPackage $sourcePackage): QtiPackage
    {
        try {
            return $this->packageBuilder->buildForTest($test, $items, $sourcePackage);
        } catch (RuntimeException $exception) {
            // buildForTest signals item/item-ref mismatches with RuntimeException.
            throw new InvalidQtiPackageException(new StringCollection([$exception->getMessage()]));
        }
    }

    private function store(QtiPackage $package): void
    {
        $this->filesystemPackageFactory->getWriter($this->folder)->write($package);
    }

    private function documentElement(Resource $resource): DOMElement
    {
        $file = $resource->getMainFile();
        if (!$file instanceof XmlFile) {
            throw new InvalidQtiPackageException(new StringCollection([sprintf('Resource "%s" has no XML file', $resource->identifier)]));
        }

        return $file->getDocumentElement();
    }
}
