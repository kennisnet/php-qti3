<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model;

use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\QtiElement;
use InvalidArgumentException;

class ItemBody extends QtiElement
{
    /**
     * Block content per the XSD's `ItemBodyDType` group; `math` is its one MathML root.
     *
     * @var array<int,string>
     */
    public const array ALLOWED_HTML_TAGS = [
        'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'address', 'dl', 'ol', 'ul', 'hr', 'blockquote',
        'table', 'div', 'article', 'aside', 'audio', 'figure', 'footer', 'header', 'nav', 'section', 'video',
        'math',
    ];

    public function __construct(
        public readonly ContentNodeCollection $content,
    ) {
        if (count($content) === 0) {
            throw new InvalidArgumentException('ItemBody must have at least one child element');
        }
    }

    /** Authoring-side check, as {@see ContentBody::allowsAsDirectChild()}. */
    public static function allowsAsDirectChild(string $tagName): bool
    {
        return in_array($tagName, self::ALLOWED_HTML_TAGS);
    }

    public function children(): array
    {
        return $this->content->all();
    }
}
