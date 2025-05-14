<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser;

use App\SharedKernel\Domain\Qti\Shared\Model\IXmlElement;
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
}
