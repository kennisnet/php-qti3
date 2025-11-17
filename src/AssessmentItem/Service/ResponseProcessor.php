<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Service;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclaration;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclarationCollection;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ParseError;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ResponseDeclarationParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ResponseProcessingParser;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclaration;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseProcessing;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\Qti\State\OutcomeSet;
use App\SharedKernel\Domain\Qti\State\ResponseSet;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;

class ResponseProcessor
{
    public function __construct(
        private readonly ResponseDeclarationParser $responseDeclarationParser,
        private readonly OutcomeDeclarationParser $outcomeDeclarationParser,
        private readonly ResponseProcessingParser $responseProcessingParser,
    ) {}

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

        $responseProcessingTag = $xmlDocument->getElementsByTagName('qti-response-processing')->item(0);

        if ($responseProcessingTag) {
            $responseProcessing = $this->responseProcessingParser->parse($responseProcessingTag);
        } else {
            $responseProcessing = new ResponseProcessing([]);
        }

        /** @var DOMElement $item */
        $item = $xmlDocument->getElementsByTagName('qti-assessment-item')->item(0);
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

        $this->validateItemState($xmlDocument, $itemState);

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

    private function validateItemState(DOMDocument $document, ItemState $itemState): void
    {
        if ($this->xpathExists($document, '//qti:qti-choice-interaction')) {
            $maxScore = $itemState->outcomeSet->getOutcomeValue('MAXSCORE');

            if (!$maxScore) {
                throw new ParseError('Missing default value for MAXSCORE outcome declaration');
            }
        }
    }

    private function xpathExists(DOMDocument $document, string $path): bool
    {
        if ($document->documentElement === null) {
            return false; // @codeCoverageIgnore
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('qti', $document->documentElement->getAttribute('xmlns'));
        $result = $xpath->query($path);

        if (!$result instanceof DOMNodeList) {
            return false; // @codeCoverageIgnore
        }

        return (bool) $result->length;
    }
}
