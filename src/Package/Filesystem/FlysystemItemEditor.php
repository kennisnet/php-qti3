<?php

declare(strict_types=1);

namespace Qti3\Package\Filesystem;

use DOMDocument;
use DOMElement;
use League\Flysystem\FilesystemOperator;
use Qti3\Package\Exception\InvalidItemOrderException;
use Qti3\Package\Exception\InvalidQtiPackageException;
use Qti3\Package\Model\IItemEditor;
use Qti3\Package\Model\Item\EditedItem;
use Qti3\Package\Service\ItemIdentifierGenerator;
use Qti3\Package\Validator\AssessmentItemValidator;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Exception\ResourceNotFoundException;
use Qti3\Shared\Xml\Reader\IXmlReader;
use RuntimeException;

/**
 * Adds and updates assessment items inside an already extracted QTI package
 * folder, operating directly on the individual files through a
 * {@see FilesystemOperator}.
 *
 * This is intentionally file-targeted rather than model-based: adding an item
 * touches only the manifest, the assessment test and the new item file, and
 * updating an item writes a single file. It never reads or rewrites the whole
 * package, keeping edits cheap regardless of package size.
 *
 * Bound to one folder; obtain one per package folder from
 * {@see \Qti3\Package\Service\IFilesystemPackageFactory::getItemEditor()}.
 */
final readonly class FlysystemItemEditor implements IItemEditor
{
    private const string MANIFEST_NAMESPACE = 'http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1';
    private const string ASI_NAMESPACE = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';
    private const string MANIFEST_FILE = 'imsmanifest.xml';
    private const string ITEM_RESOURCE_TYPE = 'imsqti_item_xmlv3p0';
    private const string TEST_RESOURCE_TYPE = 'imsqti_test_xmlv3p0';

    private string $base;

    public function __construct(
        string $folder,
        private FilesystemOperator $filesystem,
        private IXmlReader $xmlReader,
        private AssessmentItemValidator $validator,
        private ItemIdentifierGenerator $identifierGenerator,
    ) {
        $this->base = rtrim($folder, '/') . '/';
    }

    /**
     * Add a new item: assign the next identifier, write the item file, register
     * the resource in the manifest and append an item ref to the test section.
     */
    public function addItem(string $itemXml): EditedItem
    {
        $this->validator->validate($itemXml);

        // Read and patch everything in memory first, so a structural problem
        // aborts before any file is written.
        $manifest = $this->readXml($this->base . self::MANIFEST_FILE);

        $identifier = $this->identifierGenerator->nextIdentifier($this->existingItemIdentifiers($manifest));
        $href = $identifier . '.xml';

        $testHref = $this->registerManifestResource($manifest, $identifier, $href);
        $test = $this->readXml($this->base . $testHref);
        $this->appendItemRef($test, $identifier, $href);

        $itemXml = $this->normaliseIdentifier($itemXml, $identifier);

        // Only now write, grouped at the end.
        $this->filesystem->write($this->base . $href, $itemXml);
        $this->filesystem->write($this->base . self::MANIFEST_FILE, $this->save($manifest));
        $this->filesystem->write($this->base . $testHref, $this->save($test));

        return new EditedItem($identifier, $itemXml);
    }

    /**
     * Overwrite an existing item file in place. Manifest and assessment test are
     * left untouched, so existing media references keep resolving.
     */
    public function updateItem(string $identifier, string $itemXml): EditedItem
    {
        $path = $this->base . $identifier . '.xml';

        if (!$this->filesystem->fileExists($path)) {
            throw new ResourceNotFoundException('AssessmentItem', $identifier);
        }

        $this->validator->validate($itemXml);

        $itemXml = $this->normaliseIdentifier($itemXml, $identifier);
        $this->filesystem->write($path, $itemXml);

        return new EditedItem($identifier, $itemXml);
    }

    /**
     * Reorder the assessment item refs inside the test section to match the given order
     * @param list<string> $orderedIdentifiers
     */
    public function reorderItems(array $orderedIdentifiers): void
    {
        $manifest = $this->readXml($this->base . self::MANIFEST_FILE);
        $testHref = $this->testResourceHref($manifest);
        $test = $this->readXml($this->base . $testHref);

        $section = $test->getElementsByTagNameNS(self::ASI_NAMESPACE, 'qti-assessment-section')->item(0);
        if (!$section instanceof DOMElement) {
            throw new InvalidQtiPackageException(new StringCollection(['Assessment test has no section']));
        }

        // Only direct children of the section are reordered: item refs nested in child
        // sections belong to another section and are not ours to detach or re-append.
        $itemRefsByIdentifier = [];
        $currentIdentifiers = [];
        foreach ($section->childNodes as $childNode) {
            if (
                !$childNode instanceof DOMElement
                || $childNode->namespaceURI !== self::ASI_NAMESPACE
                || $childNode->localName !== 'qti-assessment-item-ref'
            ) {
                continue;
            }

            $identifier = $childNode->getAttribute('identifier');
            if ($identifier === '') {
                throw new InvalidQtiPackageException(new StringCollection(['Assessment test has an item ref without an identifier']));
            }
            if (isset($itemRefsByIdentifier[$identifier])) {
                throw new InvalidQtiPackageException(new StringCollection([sprintf('Assessment test has a duplicate item ref "%s"', $identifier)]));
            }

            $itemRefsByIdentifier[$identifier] = $childNode;
            $currentIdentifiers[] = $identifier;
        }

        $this->assertOrderMatchesItems($currentIdentifiers, $orderedIdentifiers);

        // Detach every item ref, then re-append them in the requested order.
        foreach ($itemRefsByIdentifier as $itemRef) {
            $section->removeChild($itemRef);
        }
        foreach ($orderedIdentifiers as $identifier) {
            $section->appendChild($itemRefsByIdentifier[$identifier]);
        }

        $this->filesystem->write($this->base . $testHref, $this->save($test));
    }

    /**
     * @param list<string> $currentIdentifiers
     * @param list<string> $orderedIdentifiers
     */
    private function assertOrderMatchesItems(array $currentIdentifiers, array $orderedIdentifiers): void
    {
        $errors = [];

        foreach ($this->duplicates($orderedIdentifiers) as $duplicate) {
            $errors[] = sprintf('Item "%s" is listed more than once.', $duplicate);
        }

        foreach (array_diff($orderedIdentifiers, $currentIdentifiers) as $unknown) {
            $errors[] = sprintf('Item "%s" does not exist in the test.', $unknown);
        }

        foreach (array_diff($currentIdentifiers, $orderedIdentifiers) as $missing) {
            $errors[] = sprintf('Item "%s" is missing from the new order.', $missing);
        }

        if ($errors !== []) {
            throw new InvalidItemOrderException(new StringCollection($errors));
        }
    }

    /**
     * @param list<string> $identifiers
     * @return list<string>
     */
    private function duplicates(array $identifiers): array
    {
        $counts = array_count_values($identifiers);

        return array_keys(array_filter($counts, static fn(int $count): bool => $count > 1));
    }

    /**
     * @return list<string>
     */
    private function existingItemIdentifiers(DOMDocument $manifest): array
    {
        $identifiers = [];

        foreach ($manifest->getElementsByTagNameNS(self::MANIFEST_NAMESPACE, 'resource') as $resource) {
            if ($resource->getAttribute('type') === self::ITEM_RESOURCE_TYPE) {
                $identifiers[] = $resource->getAttribute('identifier');
            }
        }

        return $identifiers;
    }

    private function registerManifestResource(DOMDocument $manifest, string $identifier, string $href): string
    {
        $resources = $manifest->getElementsByTagNameNS(self::MANIFEST_NAMESPACE, 'resources')->item(0);
        if (!$resources instanceof DOMElement) {
            throw new InvalidQtiPackageException(new StringCollection(['Manifest has no <resources> element']));
        }

        $resource = $manifest->createElementNS(self::MANIFEST_NAMESPACE, 'resource');
        $resource->setAttribute('identifier', $identifier);
        $resource->setAttribute('type', self::ITEM_RESOURCE_TYPE);
        $resource->setAttribute('href', $href);

        $file = $manifest->createElementNS(self::MANIFEST_NAMESPACE, 'file');
        $file->setAttribute('href', $href);
        $resource->appendChild($file);

        $resources->appendChild($resource);

        return $this->testResourceHref($manifest);
    }

    private function testResourceHref(DOMDocument $manifest): string
    {
        foreach ($manifest->getElementsByTagNameNS(self::MANIFEST_NAMESPACE, 'resource') as $resource) {
            if ($resource->getAttribute('type') === self::TEST_RESOURCE_TYPE) {
                return $resource->getAttribute('href');
            }
        }

        throw new InvalidQtiPackageException(new StringCollection(['Manifest has no assessment test resource']));
    }

    private function appendItemRef(DOMDocument $test, string $identifier, string $href): void
    {
        $section = $test->getElementsByTagNameNS(self::ASI_NAMESPACE, 'qti-assessment-section')->item(0);
        if (!$section instanceof DOMElement) {
            throw new InvalidQtiPackageException(new StringCollection(['Assessment test has no section']));
        }

        $itemRef = $test->createElementNS(self::ASI_NAMESPACE, 'qti-assessment-item-ref');
        $itemRef->setAttribute('identifier', $identifier);
        $itemRef->setAttribute('href', $href);
        $section->appendChild($itemRef);
    }

    private function normaliseIdentifier(string $itemXml, string $identifier): string
    {
        $dom = $this->xmlReader->read($itemXml);
        $root = $dom->documentElement;
        if ($root === null) {
            throw new RuntimeException('Invalid item XML'); // @codeCoverageIgnore
        }
        $root->setAttribute('identifier', $identifier);

        return $this->save($dom);
    }

    private function readXml(string $path): DOMDocument
    {
        return $this->xmlReader->read($this->filesystem->read($path));
    }

    private function save(DOMDocument $dom): string
    {
        $xml = $dom->saveXML();
        if ($xml === false) {
            throw new RuntimeException('Failed to serialize XML'); // @codeCoverageIgnore
        }

        return $xml;
    }
}
