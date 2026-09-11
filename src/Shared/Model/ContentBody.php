<?php

declare(strict_types=1);

namespace Qti3\Shared\Model;

final class ContentBody extends QtiElement
{
    /**
     * Flow content per the XSD's `*ContentBodyDType` groups, which differ only
     * in `details` — allowed by the rubric blocks and kept here for all of them.
     *
     * @var array<int,string>
     */
    public const array ALLOWED_HTML_TAGS = [
        'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'address', 'dl', 'ol', 'ul', 'br', 'hr', 'img', 'object',
        'blockquote', 'em', 'a', 'code', 'span', 'sub', 'acronym', 'big', 'tt', 'kbd', 'q', 'i', 'dfn', 'abbr',
        'strong', 'sup', 'var', 'small', 'samp', 'b', 'cite', 'table', 'div', 'bdo', 'bdi', 'figure', 'audio',
        'video', 'article', 'aside', 'footer', 'header', 'label', 'nav', 'section', 'ruby', 'picture', 'details',
    ];

    public function __construct(
        public ContentNodeCollection $content,
    ) {}

    /**
     * Checked where content is *authored*, not where it is parsed: a package
     * arrives without having been schema-validated, and one stray `<li>` should
     * leave it repairable rather than unreadable. Of MathML only the `math`
     * root is flow content.
     */
    public static function allowsAsDirectChild(string $tagName): bool
    {
        return in_array($tagName, self::ALLOWED_HTML_TAGS) || $tagName === 'math';
    }

    /**
     * @return array<int,IContentNode>
     */
    public function children(): array
    {
        return $this->content->all();
    }
}
