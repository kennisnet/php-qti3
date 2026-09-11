<?php

declare(strict_types=1);

namespace Qti3\Shared\Html;

use Qti3\Shared\Model\ContentBody;
use Qti3\Shared\Xml\Builder\IXmlBuilder;

/**
 * The inverse of {@see HtmlFragmentParser::parse()}. Output is XML-serialized:
 * childless elements self-close (`<br/>`) and U+00A0 stays a raw character
 * rather than becoming `&nbsp;`.
 */
final readonly class HtmlFragmentSerializer
{
    public function __construct(private IXmlBuilder $xmlBuilder) {}

    public function serialize(ContentBody $contentBody): string
    {
        $document = $this->xmlBuilder->generateXmlFromObject($contentBody);
        // The builder turns formatOutput on; a fragment must not be re-indented.
        $document->formatOutput = false;

        // The <qti-content-body> wrapper is QTI, not part of the fragment.
        $html = '';
        foreach ($document->documentElement->childNodes as $child) {
            $html .= $document->saveXML($child);
        }

        return trim($html);
    }
}
