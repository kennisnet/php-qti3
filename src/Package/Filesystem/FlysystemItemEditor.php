<?php

declare(strict_types=1);

namespace Qti3\Package\Filesystem;

use DOMDocument;
use DOMElement;
use League\Flysystem\FilesystemOperator;
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
        $this->validator->validate($itemXml);

        $path = $this->base . $identifier . '.xml';

        if (!$this->filesystem->fileExists($path)) {
            throw new ResourceNotFoundException('AssessmentItem', $identifier);
        }

        $itemXml = $this->normaliseIdentifier($itemXml, $identifier);
        $this->filesystem->write($path, $itemXml);

        return new EditedItem($identifier, $itemXml);
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
