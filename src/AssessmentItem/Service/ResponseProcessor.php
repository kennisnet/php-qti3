<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service;

use Qti3\AssessmentItem\Exception\InvalidAssessmentItemException;
use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\AssessmentItem\Model\ResponseDeclaration\ResponseDeclaration;
use Qti3\AssessmentItem\Model\ResponseDeclaration\ResponseDeclarationCollection;
use Qti3\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use Qti3\AssessmentItem\Service\Parser\ResponseDeclarationParser;
use Qti3\AssessmentItem\Service\Parser\ResponseProcessingParser;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclaration;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use Qti3\AssessmentItem\Model\ResponseProcessing\ResponseProcessing;
use Qti3\AssessmentItem\Model\State\ItemState;
use Qti3\AssessmentItem\Model\State\OutcomeSet;
use Qti3\AssessmentItem\Model\State\ResponseSet;
use DOMDocument;
use DOMElement;

class ResponseProcessor
{
    public function __construct(
        private readonly ResponseDeclarationParser $responseDeclarationParser,
        private readonly OutcomeDeclarationParser $outcomeDeclarationParser,
        private readonly ResponseProcessingParser $responseProcessingParser,
        private readonly ScoringOutcomeValidator $scoringOutcomeValidator,
    ) {}

    /**
     * Parses the item's declarations and response processing into an
     * {@see ItemState} ready for {@see self::processResponses()}.
     *
     * @throws InvalidAssessmentItemException with every scoring and processing violation of the item at once
     */
    public function initItemState(string $itemXml): ItemState
    {
        $xmlDocument = new DOMDocument();
        $xmlDocument->loadXML($itemXml);

        $responseDeclarationTags = $xmlDocument->getElementsByTagName(ResponseDeclaration::qtiTagName());
        $responseDeclarations = new ResponseDeclarationCollection();

        foreach ($responseDeclarationTags as $responseDeclarationTag) {
            $responseDeclarations->add($this->responseDeclarationParser->parse($responseDeclarationTag));
        }

        $outcomeDeclarationTags = $xmlDocument->getElementsByTagName(OutcomeDeclaration::qtiTagName());
        $outcomeDeclarations = new OutcomeDeclarationCollection();

        foreach ($outcomeDeclarationTags as $outcomeDeclarationTag) {
            $outcomeDeclarations->add($this->outcomeDeclarationParser->parse($outcomeDeclarationTag));
        }

        $responseProcessingTag = $xmlDocument->getElementsByTagName(ResponseProcessing::qtiTagName())->item(0);

        if ($responseProcessingTag) {
            $responseProcessing = $this->responseProcessingParser->parse($responseProcessingTag);
        } else {
            $responseProcessing = new ResponseProcessing([]);
        }

        /** @var DOMElement $item */
        $item = $xmlDocument->getElementsByTagName(AssessmentItem::qtiTagName())->item(0);
        $adaptive = $item->getAttribute('adaptive') === 'true';

        $itemState = new ItemState(
            new ResponseSet(
                $responseDeclarations,
            ),
            new OutcomeSet(
                $outcomeDeclarations,
            ),
            $responseProcessing,
            $adaptive,
        );

        // Root causes first (a missing declaration), then the processing
        // elements that consequently cannot resolve their identifiers; several
        // elements failing on the same identifier collapse into one line.
        $errors = $this->scoringOutcomeValidator->validate($xmlDocument, $itemState)
            ->mergeWith($responseProcessing->validate($itemState))
            ->unique();

        if (!$errors->isEmpty()) {
            throw new InvalidAssessmentItemException($errors);
        }

        return $itemState;
    }

    /**
     * @param array<string,string|int|float|bool|array<int,string|int|float|bool>|null> $responses
     */
    public function processResponses(ItemState $itemState, array $responses): void
    {
        $itemState->outcomeSet->set('completionStatus', 'unknown');
        $itemState->responseSet->setResponses($responses);

        $itemState->responseProcessing->processResponses(
            $itemState,
        );

        if ($itemState->adaptive) {
            if ($itemState->outcomeSet->getOutcomeValue('completionStatus') === 'unknown') {
                $itemState->outcomeSet->set('completionStatus', 'incomplete');
            }
        } elseif (count($itemState->responseProcessing->children()) > 0) {
            $itemState->outcomeSet->set('completionStatus', 'completed');
        }
    }

}
