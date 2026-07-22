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
            if ($element->getAttribute('template') === ResponseProcessing::TEMPLATE_MATCH_CORRECT) {
                return ResponseProcessing::matchCorrect();
            }
            if ($element->getAttribute('template') === ResponseProcessing::TEMPLATE_MAP_RESPONSE) {
                return ResponseProcessing::mapResponse();
            }
            if ($element->getAttribute('template') === ResponseProcessing::TEMPLATE_MAP_RESPONSE_POINT) {
                return ResponseProcessing::mapResponsePoint();
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
