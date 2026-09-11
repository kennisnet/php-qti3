<?php

declare(strict_types=1);

namespace Qti3\Shared\Html;

use Qti3\Shared\Model\ContentBody;
use Qti3\Shared\Xml\Builder\IXmlBuilder;

/**
 * Serializes a {@see ContentBody} back into an HTML fragment string, the
 * inverse of {@see HtmlFragmentParser::parse()}.
 *
 * The output is XML-serialized (XHTML-style): a childless element is
 * self-closing (`<br/>`, `<img .../>`), and a non-ASCII character such as
 * U+00A0 (non-breaking space) is written as the raw UTF-8 character rather
 * than a named entity like `&nbsp;`.
 */
final readonly class HtmlFragmentSerializer
{
    public function __construct(private IXmlBuilder $xmlBuilder) {}

    public function serialize(ContentBody $contentBody): string
    {
        $document = $this->xmlBuilder->generateXmlFromObject($contentBody);
        // The builder turns formatOutput on; a fragment must not be re-indented.
        $document->formatOutput = false;

        $html = '';
        foreach ($document->documentElement->childNodes as $child) {
            // The <qti-content-body> wrapper itself is QTI, not part of the
            // fragment, so only its children are serialized.
            $html .= $document->saveXML($child);
        }

        return trim($html);
    }
}
