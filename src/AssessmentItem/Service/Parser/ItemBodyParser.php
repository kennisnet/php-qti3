<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use Qti3\AssessmentItem\Model\ItemBody;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\TextNode;
use DOMElement;
use DOMNode;
use DOMText;

class ItemBodyParser extends AbstractParser
{
    public function parse(DOMElement $element): ItemBody
    {
        $this->validateTag($element, ItemBody::qtiTagName());

        $content = new ContentNodeCollection();
        foreach ($element->childNodes as $child) {
            $node = $this->parseNode($child);
            if ($node !== null) {
                $content->add($node);
            }
        }

        return new ItemBody($content);
    }

    private function parseNode(DOMNode $node): mixed
    {
        if ($node instanceof DOMText) {
            $text = $node->textContent;
            if (trim($text) === '') {
                return null;
            }
            return new TextNode($text);
        }

        if ($node instanceof DOMElement) {
            $tagName = $node->nodeName;
            
            // Check if it's an interaction or other QTI element
            // For now, we mainly support HTML tags and perhaps simple QTI elements if they exist in ItemBody
            // Based on ItemBody::ALLOWED_HTML_TAGS, we only allow certain HTML tags as direct children
            
            $attributes = [];
            foreach ($node->attributes as $attr) {
                $attributes[$attr->nodeName] = $attr->nodeValue;
            }

            $children = [];
            foreach ($node->childNodes as $child) {
                $parsedChild = $this->parseNode($child);
                if ($parsedChild !== null) {
                    $children[] = $parsedChild;
                }
            }

            return new HTMLTag($tagName, $attributes, $children);
        }

        return null;
    }
}
