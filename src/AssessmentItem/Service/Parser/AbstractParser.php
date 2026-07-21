<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use Qti3\Shared\Collection\StringCollection;
use DOMAttr;
use DOMElement;
use DOMNode;

abstract class AbstractParser
{
    protected function validateTag(DOMElement|DOMNode|null $element, string $tagName): void
    {
        if (!$element instanceof DOMElement) {
            throw new ParseError(sprintf('Expected tag "%s", no element found', $tagName));
        }

        if ($element->nodeName !== $tagName) {
            throw new ParseError(sprintf('Expected tag "%s", got "%s"', $tagName, $element->nodeName));
        }
    }

    /** @return array<int, DOMElement> */
    protected function getChildren(DOMElement $element): array
    {
        $children = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $children[] = $child;
            }
        }

        return $children;
    }

    /**
     * Record a warning for every attribute and child element of `$element` the
     * parser did not consume: the model cannot hold them, so they are silently
     * dropped when it is serialized again. Surfacing them turns silent data
     * loss into a visible warning. Namespace declarations and the XML-schema
     * bookkeeping attributes are ignored — they are re-emitted, not lost.
     *
     * @param list<string> $consumedAttributes attribute names the parser read
     * @param list<string> $consumedChildren   child element local names it read
     */
    protected function warnUnconsumed(
        DOMElement $element,
        array $consumedAttributes,
        array $consumedChildren,
        StringCollection $warnings,
    ): void {
        foreach ($element->attributes as $attribute) {
            if (!$attribute instanceof DOMAttr || $this->isNamespaceAttribute($attribute)) {
                continue;
            }
            if (!in_array($attribute->nodeName, $consumedAttributes, true)) {
                $warnings->add(sprintf('%s: drops unsupported attribute "%s"', $this->locate($element), $attribute->nodeName));
            }
        }

        foreach ($this->getChildren($element) as $child) {
            if (!in_array($child->localName, $consumedChildren, true)) {
                $warnings->add(sprintf('%s: drops unsupported element <%s>', $this->locate($child), $child->localName));
            }
        }
    }

    /**
     * A locator for `$node` so a warning can be traced back to the offending
     * element: its source line plus an identifier-based path from the document
     * root, e.g. `line 4 at /qti-assessment-test[@identifier='T']/.../qti-selection`.
     */
    protected function locate(DOMNode $node): string
    {
        return sprintf('line %d at %s', $node->getLineNo(), $this->selector($node));
    }

    private function selector(DOMNode $node): string
    {
        $segments = [];
        for ($current = $node; $current instanceof DOMElement; $current = $current->parentNode) {
            $identifier = $current->getAttribute('identifier');
            $segments[] = $current->nodeName . ($identifier !== '' ? sprintf("[@identifier='%s']", $identifier) : '');
        }

        return '/' . implode('/', array_reverse($segments));
    }

    private function isNamespaceAttribute(DOMAttr $attribute): bool
    {
        return $attribute->nodeName === 'xmlns'
            || str_starts_with($attribute->nodeName, 'xmlns:')
            || $attribute->namespaceURI === 'http://www.w3.org/2001/XMLSchema-instance';
    }

    protected function parseFloat(string $attributeValue): ?float
    {
        if ($attributeValue === '') {
            return null;
        }

        return (float) $attributeValue;
    }

    /**
     * When re-parsing serialized output, content may be wrapped in <qti-content-body>.
     * Unwrap it so both original QTI XML and serializer output are handled correctly.
     */
    protected function unwrapContentBody(DOMElement $element): DOMElement
    {
        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === 'qti-content-body') {
                return $child;
            }
        }
        return $element;
    }
}
