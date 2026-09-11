<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model;

use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\QtiElement;
use InvalidArgumentException;

class ItemBody extends QtiElement
{
    /**
     * Block content per the XSD's `ItemBodyDType` group — narrower than
     * {@see \Qti3\Shared\Model\ContentBody::ALLOWED_HTML_TAGS}, which is flow.
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
        // Not a tag out of place but a body that cannot be presented at all,
        // and the rest of the library assumes an item body has content.
        if (count($content) === 0) {
            throw new InvalidArgumentException('ItemBody must have at least one child element');
        }
    }

    /**
     * Authoring-side check, as {@see \Qti3\Shared\Model\ContentBody::allowsAsDirectChild()}.
     * `ItemBodyDType` lists `m3:math`, so the `math` root is a block here too.
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
