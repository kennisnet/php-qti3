<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use Qti3\Shared\Model\Processing\IProcessingElement;
use Qti3\AssessmentItem\Model\ResponseProcessing\ResponseProcessing;
use DOMElement;

class ResponseProcessingParser extends AbstractParser
{
    public function __construct(
        private readonly ProcessingElementParser $processingElementParser,
    ) {}

    public function parse(DOMElement $element): ResponseProcessing
    {
        $this->validateTag($element, ResponseProcessing::qtiTagName());

        if ($element->hasAttribute('template')) {
            $template = $element->getAttribute('template');

            // The template URL is kept as authored: the base URL and the
            // optional `.xml` extension vary between spec versions, only the
            // template name identifies the processing.
            $processing = match (ResponseProcessing::templateName($template)) {
                ResponseProcessing::TEMPLATE_NAME_MATCH_CORRECT => ResponseProcessing::matchCorrect(template: $template),
                ResponseProcessing::TEMPLATE_NAME_MAP_RESPONSE => ResponseProcessing::mapResponse($template),
                ResponseProcessing::TEMPLATE_NAME_MAP_RESPONSE_POINT => ResponseProcessing::mapResponsePoint($template),
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
}
