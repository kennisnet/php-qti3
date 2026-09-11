<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model;

use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\QtiElement;
use InvalidArgumentException;

class ItemBody extends QtiElement
{
    /**
     * The HTML an item body may hold as a *direct* child: the block content of
     * the XSD's `ItemBodyDType` group. Deliberately narrower than
     * {@see \Qti3\Shared\Model\ContentBody::ALLOWED_HTML_TAGS}, which is flow
     * content — an item body takes blocks only, so no `<em>` or `<img>` of its
     * own.
     *
     * @var array<int,string>
     */
    public const array ALLOWED_HTML_TAGS = [
        'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'address', 'dl', 'ol', 'ul', 'hr', 'blockquote',
        'table', 'div', 'article', 'aside', 'audio', 'figure', 'footer', 'header', 'nav', 'section', 'video',
    ];

    public function __construct(
        public readonly ContentNodeCollection $content,
    ) {
        // Unlike the content model below, an item body with nothing in it is
        // not a tag out of place but a body that cannot be presented at all,
        // and the rest of the library assumes it has content.
        if (count($content) === 0) {
            throw new InvalidArgumentException('ItemBody must have at least one child element');
        }
    }

    /**
     * Whether `$tagName` may stand as a direct child of an item body.
     *
     * Like {@see \Qti3\Shared\Model\ContentBody::allowsAsDirectChild()}, this is
     * for entry points that *author* content. Parsing stays tolerant: a package
     * reaches this library without having been schema-validated, and refusing to
     * read one over a single misplaced tag would make it unreadable rather than
     * repairable. The tag is kept as it was authored, so nothing is lost on the
     * way back out.
     *
     * `ItemBodyDType` lists `m3:math` alongside the HTML, so the `math` root is
     * a block here too; its inner elements are not.
     */
    public static function allowsAsDirectChild(string $tagName): bool
    {
        return in_array($tagName, self::ALLOWED_HTML_TAGS) || $tagName === 'math';
    }

    public function children(): array
    {
        return $this->content->all();
    }
}
