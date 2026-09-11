<?php

declare(strict_types=1);

namespace Qti3\Shared\Html;

use DOMDocument;
use DOMElement;
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
     * Parses an HTML fragment string into a {@see ContentBody}: every top-level
     * node of the fragment is run through {@see ContentNodeParser::parse()},
     * dropping nulls — which means layout whitespace between block elements is
     * dropped too, per {@see ContentNodeParser::parseText()}'s rule.
     *
     * Parsing is lenient about markup (libxml's HTML parser) but strict about
     * content: libxml's own errors and warnings are collected into `$warnings`
     * rather than thrown, while a tag or attribute outside the QTI whitelist
     * throws.
     *
     * @throws InvalidArgumentException from {@see ContentNodeParser::parse()}
     *         (via {@see HTMLTag}'s constructor) when the fragment uses a tag
     *         or attribute outside the QTI HTML whitelist, and from this method
     *         when a top-level tag is valid HTML but cannot stand as a direct
     *         child of a content body (see {@see ContentBody::allowsAsDirectChild()}).
     * @throws HtmlParsingException from {@see self::loadFragment()}.
     */
    public function parse(string $html, ?StringCollection $warnings = null): ContentBody
    {
        $body = $this->loadFragment($html, $warnings);

        $content = new ContentNodeCollection();
        foreach ($body->childNodes as $child) {
            $node = $this->contentNodeParser->parse($child);
            if ($node === null) {
                continue;
            }

            if ($node instanceof HTMLTag && !ContentBody::allowsAsDirectChild($node->tagName())) {
                throw new InvalidArgumentException(sprintf('HTML tag %s is not allowed as direct child of a content body', $node->tagName()));
            }

            $content->add($node);
        }

        return new ContentBody($content);
    }

    /**
     * Leniently parses an HTML fragment with libxml and returns the `<body>`
     * element holding the fragment's top-level nodes.
     *
     * The fragment is wrapped as `<html><body>...</body></html>` with a leading
     * XML declaration — that declaration is what makes libxml read the fragment
     * as UTF-8 instead of defaulting to ISO-8859-1. Parsing uses
     * `LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD` so libxml adds no wrapper
     * of its own: bare text stays a bare text node instead of being wrapped in
     * an implied `<p>`.
     *
     * Parse errors and warnings are appended to `$warnings`, each formatted as
     * `sprintf('line %d: %s', $error->line, trim($error->message))`; they do not
     * stop parsing. The global libxml internal-error-handling flag is saved
     * before parsing and restored afterwards, and the internal error buffer is
     * cleared, so this call leaves no state behind for the rest of the process.
     *
     * @throws HtmlParsingException if no `<body>` element results; unreachable
     *         given the fixed wrapper markup above.
     */
    private function loadFragment(string $html, ?StringCollection $warnings = null): DOMElement
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
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if (!$body instanceof DOMElement) {
            throw new HtmlParsingException('Failed to parse HTML fragment: no <body> element found'); // @codeCoverageIgnore
        }

        return $body;
    }

    /**
     * libxml's HTML parser knows only HTML, so it reports every MathML element
     * as "Tag mi invalid" even though the QTI content model accepts MathML and
     * this parser keeps it. Suppressing that noise keeps `$warnings` a list of
     * things the caller can act on; a tag the model does *not* accept is left
     * in, and is thrown on later by {@see HTMLTag} anyway.
     */
    private function isKnownTagComplaint(LibXMLError $error): bool
    {
        if (preg_match('/^Tag (\S+) invalid$/', trim($error->message), $matches) !== 1) {
            return false;
        }

        return in_array($matches[1], HTMLTag::MATHML_TAGS, true);
    }
}
