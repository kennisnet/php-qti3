<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use Qti3\Shared\Model\IXmlElement;
use DOMElement;
use DOMNode;

abstract class AbstractParser
{
    abstract public function parse(DOMElement $element): IXmlElement;

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
