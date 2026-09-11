<?php

declare(strict_types=1);

namespace Qti3\Shared\Html;

use DOMComment;
use DOMElement;
use DOMNode;
use DOMText;
use InvalidArgumentException;
use Qti3\Shared\Model\Comment;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\IContentNode;
use Qti3\Shared\Model\TextNode;

/**
 * Parses a DOM node into the {@see IContentNode} tree of a
 * {@see \Qti3\Shared\Model\ContentBody}. Node types the model has no place for
 * are dropped.
 *
 * @throws InvalidArgumentException from {@see HTMLTag} for a tag or attribute
 *         outside the QTI HTML whitelist.
 */
final readonly class ContentNodeParser
{
    /**
     * The tags whose surrounding whitespace is layout rather than content.
     * Deliberately not {@see HTMLTag::getBlockTags()}, which splits tags by the
     * QTI content model; whitespace significance follows CSS display instead.
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
     * The QTI elements that flow inline with the surrounding words. Every other
     * one is a container, whose surrounding whitespace is layout.
     *
     * @var array<int,string>
     */
    public const array INLINE_QTI_TAGS = [
        'qti-feedback-inline', 'qti-gap', 'qti-hottext', 'qti-inline-choice-interaction',
        'qti-text-entry-interaction',
    ];

    public function parse(DOMNode $node): ?IContentNode
    {
        if ($node instanceof DOMText) {
            return $this->parseText($node);
        }

        if ($node instanceof DOMComment) {
            return new Comment($node->textContent);
        }

        if ($node instanceof DOMElement) {
            return new HTMLTag($node->nodeName, $this->attributesOf($node), $this->parseChildren($node));
        }

        return null;
    }

    /**
     * A text node, or null when it carries nothing but layout whitespace.
     * Whitespace-only text is content where it separates inline content —
     * `<strong>a</strong> <em>b</em>` is two words — and layout where a block
     * element sits on either side. Public because the parsers that do their own
     * element dispatch call it, so the rule lives in one place.
     */
    public function parseText(DOMText $node): ?TextNode
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
    private function parseChildren(DOMElement $element): array
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
    private function attributesOf(DOMElement $element): array
    {
        $attributes = [];
        foreach ($element->attributes as $attribute) {
            $attributes[$attribute->nodeName] = $attribute->nodeValue;
        }

        return $attributes;
    }

    /** Both sides have to be inline for the whitespace to be a word boundary. */
    private function separatesInlineContent(DOMText $node): bool
    {
        $parent = $node->parentNode;
        $atInlineEdge = $parent instanceof DOMElement && $this->isInlineTag($parent->nodeName);

        return $this->isInlineNeighbour($node->previousSibling, $atInlineEdge)
            && $this->isInlineNeighbour($node->nextSibling, $atInlineEdge);
    }

    /**
     * A missing sibling means the whitespace sits at an edge of its parent,
     * which is content only inside an inline parent (`a<em> </em>b`).
     */
    private function isInlineNeighbour(?DOMNode $sibling, bool $atInlineEdge): bool
    {
        if ($sibling === null) {
            return $atInlineEdge;
        }

        if ($sibling instanceof DOMText) {
            return trim($sibling->textContent) !== '';
        }

        return $sibling instanceof DOMElement && $this->isInlineTag($sibling->nodeName);
    }

    /**
     * Anything unrecognised — a MathML root, a QTI container, an unknown tag —
     * counts as block-level, keeping the whitespace handling it had before.
     */
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
