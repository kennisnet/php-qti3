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
     * Lenient about markup, strict about content: libxml's complaints land in
     * `$warnings`, a tag outside the QTI whitelist throws.
     *
     * @throws InvalidArgumentException for a tag or attribute outside the QTI
     *         HTML whitelist, or a top-level tag that cannot stand as a direct
     *         child of a content body.
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
     * The leading XML declaration is what makes libxml read the fragment as
     * UTF-8 instead of ISO-8859-1; `NOIMPLIED|NODEFDTD` stops it wrapping bare
     * text in an implied `<p>`. The global libxml error flag is restored and
     * the buffer cleared, so the call leaves no state behind.
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
     * libxml knows no MathML and reports every MathML element as "Tag mi
     * invalid". The model accepts it, so that noise would make `$warnings`
     * unusable; a tag the model rejects is thrown on by {@see HTMLTag} anyway.
     */
    private function isKnownTagComplaint(LibXMLError $error): bool
    {
        if (preg_match('/^Tag (\S+) invalid$/', trim($error->message), $matches) !== 1) {
            return false;
        }

        return in_array($matches[1], HTMLTag::MATHML_TAGS, true);
    }
}
