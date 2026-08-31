<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use Qti3\Shared\Model\Processing\IProcessingElement;
use Qti3\AssessmentItem\Model\ResponseProcessing\ResponseProcessing;
use DOMElement;

class ResponseProcessingParser extends AbstractParser
{
    private const string TEMPLATE_NAME_MATCH_CORRECT = 'match_correct';
    private const string TEMPLATE_NAME_MAP_RESPONSE = 'map_response';
    private const string TEMPLATE_NAME_MAP_RESPONSE_POINT = 'map_response_point';

    public function __construct(
        private readonly ProcessingElementParser $processingElementParser,
    ) {}

    public function parse(DOMElement $element): ResponseProcessing
    {
        $this->validateTag($element, ResponseProcessing::qtiTagName());

        if ($element->hasAttribute('template')) {
            // Any spelling of a standard template - a legacy v2p1 URL, a
            // different base URL, or a missing `.xml` extension - resolves to
            // the same processing and is rewritten to the canonical v3p0 URL.
            $processing = match ($this->templateName($element->getAttribute('template'))) {
                self::TEMPLATE_NAME_MATCH_CORRECT => ResponseProcessing::matchCorrect(),
                self::TEMPLATE_NAME_MAP_RESPONSE => ResponseProcessing::mapResponse(),
                self::TEMPLATE_NAME_MAP_RESPONSE_POINT => ResponseProcessing::mapResponsePoint(),
                default => null,
            };

            if ($processing !== null) {
                return $processing;
            }
        }

        return new ResponseProcessing(
            array_map(
                fn($child): IProcessingElement => $this->processingElementParser->parse($child),
                $this->getChildren($element),
            ),
        );
    }

    /**
     * Resolves a response processing template URL to the name of the standard
     * template it refers to, or null when the URL is not a known template.
     *
     * The specification publishes the templates under several base URLs
     * (`purl.imsglobal.org/spec/qti/v3p0/rptemplates/`,
     * `www.imsglobal.org/question/qti_v2p1/rptemplates/`, ...) and the `.xml`
     * extension is optional, so only the template file name is significant.
     */
    private function templateName(string $template): ?string
    {
        if (preg_match('~/rptemplates/([^/?\#]+)$~i', trim($template), $matches) !== 1) {
            return null;
        }

        $name = strtolower(preg_replace('/\.xml$/i', '', $matches[1]) ?? '');

        return match ($name) {
            self::TEMPLATE_NAME_MATCH_CORRECT,
            self::TEMPLATE_NAME_MAP_RESPONSE,
            self::TEMPLATE_NAME_MAP_RESPONSE_POINT => $name,
            default => null,
        };
    }
}
