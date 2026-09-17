<?php

declare(strict_types=1);

namespace Qti3\Shared\Model;

final class ContentBody extends QtiElement
{
    /**
     * Flow content per the XSD's `*ContentBodyDType` groups; `math` is the one MathML root among them.
     *
     * @var array<int,string>
     */
    public const array ALLOWED_HTML_TAGS = [
        'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'address', 'dl', 'ol', 'ul', 'br', 'hr', 'img',
        'blockquote', 'em', 'a', 'code', 'span', 'sub', 'acronym', 'big', 'tt', 'kbd', 'q', 'i', 'dfn', 'abbr',
        'strong', 'sup', 'var', 'small', 'samp', 'b', 'cite', 'table', 'div', 'bdo', 'bdi', 'figure', 'audio',
        'video', 'article', 'aside', 'footer', 'header', 'label', 'nav', 'section', 'ruby', 'picture', 'details',
        'math',
    ];

    /** Tags that carry content on their own, regardless of children (e.g. an empty <table/>). */
    private const array CONTENT_CARRYING_TAGS = [
        'img', 'hr', 'table', 'video', 'audio', 'object', 'picture',
    ];

    public function __construct(
        public ContentNodeCollection $content,
    ) {}

    /** Enforced where content is authored; parsing keeps the tag and warns instead. */
    public static function allowsAsDirectChild(string $tagName): bool
    {
        return in_array($tagName, self::ALLOWED_HTML_TAGS);
    }

    /**
     * @return array<int,IContentNode>
     */
    public function children(): array
    {
        return $this->content->all();
    }

    /** Whether the body carries anything visually meaningful, as opposed to e.g. `<p><br></p>`. */
    public function hasContent(): bool
    {
        foreach ($this->content as $node) {
            if (self::nodeHasContent($node)) {
                return true;
            }
        }

        return false;
    }

    private static function nodeHasContent(IContentNode $node): bool
    {
        // A Comment is a TextNode subtype but carries no visible content.
        if ($node instanceof Comment) {
            return false;
        }

        if ($node instanceof TextNode) {
            return self::textHasContent($node->content);
        }

        if ($node instanceof HTMLTag) {
            if (in_array($node->tagName(), self::CONTENT_CARRYING_TAGS, true)) {
                return true;
            }

            foreach ($node->children() as $child) {
                if (self::nodeHasContent($child)) {
                    return true;
                }
            }

            return false;
        }

        // Interactions, feedback blocks, etc. — none of these can be visually empty.
        return true;
    }

    private static function textHasContent(string $text): bool
    {
        $stripped = str_replace(["\u{00A0}", "\u{200B}", "\u{FEFF}"], '', $text);

        return trim($stripped) !== '';
    }
}
