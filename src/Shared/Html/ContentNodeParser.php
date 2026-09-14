<?php

declare(strict_types=1);

namespace Qti3\Shared\Html;

use DOMComment;
use DOMElement;
use DOMNode;
use DOMText;
use Dom\Comment as HtmlComment;
use Dom\Element as HtmlElement;
use Dom\Node as HtmlNode;
use Dom\Text as HtmlText;
use InvalidArgumentException;
use Qti3\Shared\Model\Comment;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\IContentNode;
use Qti3\Shared\Model\TextNode;

/**
 * Parses a DOM node into an {@see IContentNode} tree; node types the model has no place for are dropped.
 * Reads both DOM APIs: the QTI parsers hand it classic `DOM*` nodes, the HTML5 parser `Dom\*` ones.
 *
 * @throws InvalidArgumentException from {@see HTMLTag} for a tag or attribute outside the QTI whitelist.
 */
final readonly class ContentNodeParser
{
    /**
     * Tags whose surrounding whitespace is layout. Follows CSS display, not
     * {@see HTMLTag::getBlockTags()}, which splits tags by the QTI content model.
     *
     * @var array<int,string>
     */
    public const array WHITESPACE_INSIGNIFICANT_TAGS = [
        'address', 'article', 'aside', 'audio', 'blockquote', 'caption', 'col', 'colgroup', 'dd', 'details',
        'div', 'dl', 'dt', 'figcaption', 'figure', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header',
        'hr', 'li', 'nav', 'object', 'ol', 'p', 'param', 'picture', 'pre', 'section', 'source', 'summary',
        'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'track', 'ul', 'video',
    ];

    /**
     * The QTI elements that flow inline with the surrounding words.
     *
     * @var array<int,string>
     */
    public const array INLINE_QTI_TAGS = [
        'qti-feedback-inline', 'qti-gap', 'qti-hottext', 'qti-inline-choice-interaction',
        'qti-text-entry-interaction',
    ];

    public function parse(DOMNode|HtmlNode $node): ?IContentNode
    {
        if ($node instanceof DOMText || $node instanceof HtmlText) {
            return $this->parseText($node);
        }

        if ($node instanceof DOMComment || $node instanceof HtmlComment) {
            return new Comment($node->textContent);
        }

        if ($node instanceof DOMElement || $node instanceof HtmlElement) {
            // `localName`, not `nodeName`: the HTML5 parser reports element names uppercased.
            return new HTMLTag($node->localName, $this->attributesOf($node), $this->parseChildren($node));
        }

        return null;
    }

    /**
     * Whitespace-only text is content where it separates inline content
     * (`<strong>a</strong> <em>b</em>` is two words) and layout next to a block.
     */
    public function parseText(DOMText|HtmlText $node): ?TextNode
    {
        if (trim($node->textContent) !== '') {
            return new TextNode($node->textContent);
        }

        if ($this->separatesInlineContent($node)) {
            return new TextNode($node->textContent);
        }

        return null;
    }

    /**
     * @return array<int,IContentNode>
     */
    private function parseChildren(DOMElement|HtmlElement $element): array
    {
        $children = [];
        foreach ($element->childNodes as $child) {
            $parsedChild = $this->parse($child);
            if ($parsedChild !== null) {
                $children[] = $parsedChild;
            }
        }

        return $children;
    }

    /**
     * @return array<string,string|null>
     */
    private function attributesOf(DOMElement|HtmlElement $element): array
    {
        $attributes = [];
        foreach ($element->attributes as $attribute) {
            $attributes[$attribute->nodeName] = $attribute->nodeValue;
        }

        return $attributes;
    }

    /** Both sides have to be inline for the whitespace to be a word boundary. */
    private function separatesInlineContent(DOMText|HtmlText $node): bool
    {
        $parent = $node->parentNode;
        $atInlineEdge = ($parent instanceof DOMElement || $parent instanceof HtmlElement)
            && $this->isInlineTag($parent->localName);

        return $this->isInlineNeighbour($node->previousSibling, $atInlineEdge)
            && $this->isInlineNeighbour($node->nextSibling, $atInlineEdge);
    }

    /** A missing sibling is an edge of the parent, which is content only inside an inline parent. */
    private function isInlineNeighbour(DOMNode|HtmlNode|null $sibling, bool $atInlineEdge): bool
    {
        if ($sibling === null) {
            return $atInlineEdge;
        }

        if ($sibling instanceof DOMText || $sibling instanceof HtmlText) {
            return trim($sibling->textContent) !== '';
        }

        return ($sibling instanceof DOMElement || $sibling instanceof HtmlElement)
            && $this->isInlineTag($sibling->localName);
    }

    /** Anything unrecognised — MathML, a QTI container, an unknown tag — counts as block. */
    private function isInlineTag(string $tagName): bool
    {
        if (in_array($tagName, self::INLINE_QTI_TAGS, true)) {
            return true;
        }

        return $this->isHtmlTag($tagName) && !in_array($tagName, self::WHITESPACE_INSIGNIFICANT_TAGS, true);
    }

    private function isHtmlTag(string $tagName): bool
    {
        static $htmlTags = null;
        if ($htmlTags === null) {
            $htmlTags = array_merge(...array_column(HTMLTag::HTML_TAG_GROUPS, 'tags'));
        }

        return in_array($tagName, $htmlTags, true);
    }
}
