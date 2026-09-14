<?php

declare(strict_types=1);

namespace Qti3\Shared\Html;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;
use InvalidArgumentException;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Model\ContentBody;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\HTMLTag;

/**
 * Turns an HTML fragment string, as produced by a rich-text editor, into the
 * library's content model without the caller having to build any DOM itself.
 */
final readonly class HtmlFragmentParser
{
    public function __construct(private ContentNodeParser $contentNodeParser) {}

    /**
     * Lenient about markup, strict about content. Defaults to a content body's flow content;
     * pass `$allowsAsDirectChild` for a narrower target, such as `ItemBody::allowsAsDirectChild(...)`.
     *
     * @param (callable(string): bool)|null $allowsAsDirectChild
     *
     * @throws InvalidArgumentException for a tag or attribute outside the QTI whitelist,
     *         or a top-level tag the target body cannot hold.
     */
    public function parse(string $html, ?StringCollection $warnings = null, ?callable $allowsAsDirectChild = null): ContentBody
    {
        $allowsAsDirectChild ??= ContentBody::allowsAsDirectChild(...);

        $content = new ContentNodeCollection();
        foreach ($this->loadFragment($html, $warnings) as $child) {
            $node = $this->contentNodeParser->parse($child);
            if ($node === null) {
                continue;
            }

            if ($node instanceof HTMLTag && !$allowsAsDirectChild($node->tagName())) {
                throw new InvalidArgumentException(sprintf('HTML tag %s is not allowed as direct child of a content body', $node->tagName()));
            }

            $content->add($node);
        }

        return new ContentBody($content);
    }

    /**
     * PHP's HTML5 parser repairs what an editor emits — unclosed tags, valueless attributes,
     * `&nbsp;`, a stray `</body>` — and knows MathML and the HTML5 elements, so its complaints
     * are real ones and go to `$warnings` unfiltered.
     *
     * @return array<int,Node>
     */
    private function loadFragment(string $html, ?StringCollection $warnings = null): array
    {
        $previous = libxml_use_internal_errors(true);
        try {
            // The doctype keeps the parser out of quirks mode, whose complaint would otherwise
            // open every fragment; the explicit <body> keeps a <script> or <style> out of the head.
            $document = HTMLDocument::createFromString('<!DOCTYPE html><html><body>' . $html . '</body></html>', 0, 'UTF-8');

            foreach (libxml_get_errors() as $error) {
                $warnings?->add(sprintf('line %d: %s', $error->line, trim($error->message)));
            }
        } finally {
            if ($previous === false) {
                libxml_clear_errors();
            }
            libxml_use_internal_errors($previous);
        }

        $body = $document->body;
        if (!$body instanceof Element) {
            throw new HtmlParsingException('Failed to parse HTML fragment: no <body> element found'); // @codeCoverageIgnore
        }

        return iterator_to_array($body->childNodes);
    }
}
