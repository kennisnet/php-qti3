<?php

declare(strict_types=1);

namespace Qti3\Package\Model\PackageFile;

use DOMElement;
use Qti3\Package\Exception\InvalidItemOrderException;
use Qti3\Package\Exception\InvalidQtiPackageException;
use Qti3\Shared\Collection\StringCollection;

/**
 * The assessment test XML file of a QTI package, with the domain operations on
 * its item refs. Mirrors {@see \Qti3\Package\Model\Manifest\Manifest}: an
 * {@see XmlFile} that knows the structure of its own document.
 */
class AssessmentTestFile extends XmlFile
{
    private const string ASI_NAMESPACE = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';

    /**
     * Append an item ref to the test section.
     */
    public function addItemRef(string $identifier, string $href): void
    {
        $itemRef = $this->getXml()->createElementNS(self::ASI_NAMESPACE, 'qti-assessment-item-ref');
        $itemRef->setAttribute('identifier', $identifier);
        $itemRef->setAttribute('href', $href);

        $this->getSection()->appendChild($itemRef);
    }

    /**
     * Rewrite the item refs of the test section to match the given order.
     * @param list<string> $orderedIdentifiers
     */
    public function reorderItemRefs(array $orderedIdentifiers): void
    {
        $section = $this->getSection();

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

        $this->assertOrderMatchesItemRefs($currentIdentifiers, $orderedIdentifiers);

        // Detach every item ref, then re-append them in the requested order.
        foreach ($itemRefsByIdentifier as $itemRef) {
            $section->removeChild($itemRef);
        }
        foreach ($orderedIdentifiers as $identifier) {
            $section->appendChild($itemRefsByIdentifier[$identifier]);
        }
    }

    private function getSection(): DOMElement
    {
        $section = $this->getXml()->getElementsByTagNameNS(self::ASI_NAMESPACE, 'qti-assessment-section')->item(0);
        if (!$section instanceof DOMElement) {
            throw new InvalidQtiPackageException(new StringCollection(['Assessment test has no section']));
        }

        return $section;
    }

    /**
     * @param list<string> $currentIdentifiers
     * @param list<string> $orderedIdentifiers
     */
    private function assertOrderMatchesItemRefs(array $currentIdentifiers, array $orderedIdentifiers): void
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

        return array_map('strval', array_keys(array_filter($counts, static fn(int $count): bool => $count > 1)));
    }
}
