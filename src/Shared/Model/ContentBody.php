<?php

declare(strict_types=1);

namespace Qti3\Shared\Model;

final class ContentBody extends QtiElement
{
    /**
     * The HTML a content body may hold as a *direct* child: the flow content of
     * the XSD's `*ContentBodyDType` groups, which are identical across rubric
     * blocks, test rubric blocks and the feedback variants but for `details`,
     * which only the rubric blocks allow and which is kept here for all of them
     * rather than splitting the model into five.
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
     * Whether `$tagName` may stand as a direct child of a content body. A tag
     * can be valid HTML and still be meaningless on its own — a stray `<li>` or
     * `<td>` outside its list or table serializes to schema-invalid QTI — so an
     * entry point that *authors* content, such as
     * {@see \Qti3\Shared\Html\HtmlFragmentParser::parse()}, checks this before
     * building a body.
     *
     * Parsing deliberately does not: a package reaches this library without
     * having been schema-validated, and refusing to read one over a single
     * misplaced tag would make it unreadable rather than repairable — the same
     * reason the parsers warn instead of throwing elsewhere. The tag is kept as
     * it was authored, so nothing is lost on the way back out.
     *
     * Of MathML only the `math` root is flow content; its inner elements are no
     * more free-standing than an `<li>` is.
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
