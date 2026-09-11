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
 * Parses a single DOM node into the {@see IContentNode} model tree used inside
 * a {@see \Qti3\Shared\Model\ContentBody}: a `DOMText` becomes a
 * {@see TextNode} (dropped when it is layout whitespace, see
 * {@see self::parseText()}), a `DOMComment` becomes a {@see Comment}, a
 * `DOMElement` becomes an {@see HTMLTag} carrying its attributes and its
 * recursively parsed children (children that parse to null are dropped), and
 * any other node type (processing instructions, ...) is dropped.
 *
 * @throws InvalidArgumentException when an element's tag name, or one of its
 *         attributes, falls outside the QTI HTML whitelist enforced by
 *         {@see HTMLTag}'s constructor; the exception propagates unchanged.
 */
final readonly class ContentNodeParser
{
    /**
     * The tags whose surrounding whitespace is layout rather than content.
     *
     * This is deliberately not {@see HTMLTag::getBlockTags()}: that list splits
     * tags by the QTI content model (where `p` and `blockquote` sit among the
     * inline group), while whitespace significance follows CSS display — a
     * newline between two `</p><p>` renders as nothing, a space between
     * `</strong>` and `<em>` renders as a word boundary.
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
     * The QTI elements that flow inline with the surrounding words, so that the
     * whitespace around them is a word boundary. Every other QTI element — an
     * item body, a rubric block, a block-level interaction — is a container
     * whose surrounding whitespace is layout.
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
            // The model can hold a comment, so keep it rather than silently
            // dropping content the author wrote.
            return new Comment($node->textContent);
        }

        if ($node instanceof DOMElement) {
            return new HTMLTag($node->nodeName, $this->attributesOf($node), $this->parseChildren($node));
        }

        return null;
    }

    /**
     * A text node, or null when it carries nothing but layout whitespace.
     *
     * Whitespace-only text is content wherever it separates inline content —
     * `<strong>a</strong> <em>b</em>` is two words, and dropping the space
     * glues them together. It is layout wherever a block element sits on
     * either side (`</p>\n  <p>`, or `<p>\n  <strong>x</strong>\n</p>`, which
     * renders without either newline), and only those are dropped. Shared with the parsers that do
     * their own element dispatch ({@see \Qti3\AssessmentItem\Service\Parser\ItemBodyParser},
     * {@see \Qti3\AssessmentItem\Service\Parser\InteractionParser}) so the rule
     * lives in one place.
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

    /**
     * Whether the whitespace-only `$node` separates two pieces of inline
     * content. Both sides have to be inline: whitespace with a block element on
     * either side renders as nothing — a newline between `</div>` and the next
     * element, or between `<p>` and the `<strong>` that opens it — while
     * whitespace between two inline pieces is the word boundary between them.
     */
    private function separatesInlineContent(DOMText $node): bool
    {
        $parent = $node->parentNode;
        $atInlineEdge = $parent instanceof DOMElement && $this->isInlineTag($parent->nodeName);

        return $this->isInlineNeighbour($node->previousSibling, $atInlineEdge)
            && $this->isInlineNeighbour($node->nextSibling, $atInlineEdge);
    }

    /**
     * Whether whitespace next to `$sibling` separates inline content.
     *
     * A missing sibling means the whitespace sits at an edge of its parent,
     * which is content only inside an inline parent (`a<em> </em>b`) — hence
     * `$atInlineEdge`.
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
     * Inline is the narrow set: an HTML tag that is not block-level, or one of
     * the inline QTI elements. Everything else — a MathML root, a QTI container,
     * an unknown tag — counts as block-level, so unrecognised markup keeps the
     * whitespace handling the library had before this rule existed.
     */
    private function isInlineTag(string $tagName): bool
    {
        if (in_array($tagName, self::INLINE_QTI_TAGS, true)) {
            return true;
        }

        return $this->isHtmlTag($tagName) && !in_array($tagName, self::WHITESPACE_INSIGNIFICANT_TAGS, true);
    }

    /** Whether `$tagName` is HTML the QTI content model knows (MathML is not). */
    private function isHtmlTag(string $tagName): bool
    {
        static $htmlTags = null;
        if ($htmlTags === null) {
            $htmlTags = array_merge(...array_column(HTMLTag::HTML_TAG_GROUPS, 'tags'));
        }

        return in_array($tagName, $htmlTags, true);
    }
}
