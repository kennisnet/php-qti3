<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service;

use Qti3\AssessmentItem\Model\State\ItemState;
use Qti3\Shared\Collection\StringCollection;
use DOMDocument;
use DOMNodeList;
use DOMXPath;

/**
 * Guards the scoring outcomes of a single assessment item: a question that
 * declares response processing must declare a SCORE outcome for it to land in,
 * its processing must actually set SCORE, and it must carry a usable MAXSCORE.
 *
 * Every violation is collected and returned, never thrown: the caller (see
 * {@see ResponseProcessor::initItemState()}) merges them with the response
 * processing's own validation so an author sees everything at once.
 *
 * Works on the parsed {@see ItemState} for everything the typed model knows
 * (declarations, defaults) and falls back to the item DOM only for questions
 * about what the source file contains: which interactions are present and
 * whether a `qti-response-processing` element is declared at all. The latter
 * must be a DOM question because the parser drops unknown template URLs.
 */
final readonly class ScoringOutcomeValidator
{
    private const string SCORE = 'SCORE';
    private const string MAXSCORE = 'MAXSCORE';

    public function __construct(
        private AssessmentItemDeterminator $assessmentItemDeterminator,
    ) {}

    /**
     * @return StringCollection every scoring violation of the item; empty when it is scorable as declared
     */
    public function validate(DOMDocument $document, ItemState $itemState): StringCollection
    {
        $errors = new StringCollection();

        if ($this->assessmentItemDeterminator->determineType($document) !== 'question') {
            return $errors;
        }

        $declared = $itemState->outcomeSet->outcomeDeclarations->getIdentifiers();

        // Any response processing - inline, empty, or referring to a known or
        // unknown template - needs a SCORE outcome to land in: the player only
        // enables checking an item when the SCORE variable exists.
        if ($this->xpathExists($document, '//ns:qti-response-processing') && !$declared->has(self::SCORE)) {
            $errors->add('Missing `qti-outcome-declaration` with identifier `SCORE`');
        }

        $hasInteractionNotText = $this->xpathExists($document, '//ns:qti-item-body//*[starts-with(name(), "qti-") and substring(name(), string-length(name()) - 11) = "-interaction" and name() != "qti-extended-text-interaction"]');
        $hasProcessingScore = $this->xpathExists($document, '//ns:qti-response-processing//ns:qti-set-outcome-value[@identifier="SCORE"]');
        $processingTemplate = $this->xpathExists($document, '//ns:qti-response-processing[string-length(@template) > 2]');
        $hasResponseProcessingContent = $this->xpathExists($document, '//ns:qti-response-processing[*]');

        if ($hasInteractionNotText && $hasResponseProcessingContent && !$hasProcessingScore && !$processingTemplate) {
            $errors->add('Missing `set-outcome-value` with identifier `SCORE` in `response-processing`');
        }

        if (!$this->assessmentItemDeterminator->determineManualScoring($document)) {
            $maxScore = $itemState->outcomeSet->getOutcomeValue(self::MAXSCORE);

            if (!is_numeric($maxScore) || (float) $maxScore < 0) {
                $errors->add('Missing default value for MAXSCORE outcome declaration');
            }
        }

        return $errors;
    }

    private function xpathExists(DOMDocument $document, string $path): bool
    {
        if ($document->documentElement === null) {
            return false; // @codeCoverageIgnore
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('ns', $document->documentElement->getAttribute('xmlns'));
        $result = $xpath->query($path);

        if (!$result instanceof DOMNodeList) {
            return false; // @codeCoverageIgnore
        }

        return (bool) $result->length;
    }
}
