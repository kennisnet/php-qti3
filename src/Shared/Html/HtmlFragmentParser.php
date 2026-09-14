<?php

declare(strict_types=1);

namespace Qti3\Shared\Html;

use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;
use LibXMLError;
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
     * The leading XML declaration makes libxml read the fragment as UTF-8 instead of
     * ISO-8859-1; `NOIMPLIED|NODEFDTD` stops it wrapping bare text in an implied `<p>`.
     *
     * @return array<int,DOMNode>
     */
    private function loadFragment(string $html, ?StringCollection $warnings = null): array
    {
        $document = new DOMDocument();

        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML(
                '<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
            );

            foreach (libxml_get_errors() as $error) {
                if ($this->isKnownTagComplaint($error)) {
                    continue;
                }
                $warnings?->add(sprintf('line %d: %s', $error->line, trim($error->message)));
            }
        } finally {
            if ($previous === false) {
                libxml_clear_errors();
            }
            libxml_use_internal_errors($previous);
        }

        $root = $document->documentElement;
        if (!$root instanceof DOMElement) {
            throw new HtmlParsingException('Failed to parse HTML fragment: no root element found'); // @codeCoverageIgnore
        }

        // A `</body>` in the fragment closes the wrapper early and leaves the rest beside it.
        $nodes = [];
        foreach ($root->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'body') {
                foreach ($child->childNodes as $grandchild) {
                    $nodes[] = $grandchild;
                }
                continue;
            }

            $nodes[] = $child;
        }

        return $nodes;
    }

    /**
     * libxml's HTML parser predates HTML5 and knows no MathML, so it reports `<figure>`
     * and `<mi>` as "Tag x invalid"; a tag the model rejects is thrown on by {@see HTMLTag}.
     */
    private function isKnownTagComplaint(LibXMLError $error): bool
    {
        if (preg_match('/^Tag (\S+) invalid$/', trim($error->message), $matches) !== 1) {
            return false;
        }

        return in_array($matches[1], HTMLTag::allowedTagNames(), true);
    }
}
