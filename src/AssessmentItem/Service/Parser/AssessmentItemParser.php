<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\AssessmentItem\Model\AssessmentItemId;
use Qti3\AssessmentItem\Model\ItemBody;
use Qti3\AssessmentItem\Model\ResponseDeclaration\ResponseDeclaration;
use Qti3\AssessmentItem\Model\ResponseDeclaration\ResponseDeclarationCollection;
use Qti3\AssessmentItem\Model\ResponseProcessing\ResponseProcessing;
use Qti3\AssessmentItem\Model\Feedback\ModalFeedback;
use Qti3\AssessmentItem\Model\Stylesheet\Stylesheet;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclaration;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use Qti3\Shared\Xml\Reader\IXmlReader;
use Qti3\Shared\Xml\Reader\XmlParsingException;
use DOMElement;

class AssessmentItemParser extends AbstractParser
{
    public function __construct(
        private readonly ResponseDeclarationParser $responseDeclarationParser,
        private readonly OutcomeDeclarationParser $outcomeDeclarationParser,
        private readonly ItemBodyParser $itemBodyParser,
        private readonly ResponseProcessingParser $responseProcessingParser,
        private readonly StylesheetParser $stylesheetParser,
        private readonly ModalFeedbackParser $modalFeedbackParser,
        private readonly IXmlReader $xmlReader,
    ) {}

    /**
     * Parse an assessment item from its XML string; malformed XML surfaces as a
     * {@see ParseError}, like every other parse failure.
     *
     * @throws ParseError
     */
    /**
     * Parse an assessment item from its XML string; malformed XML surfaces as a
     * {@see ParseError}, like every other parse failure. Constructs the model
     * cannot hold are not refused but reported through the result's warnings.
     *
     * @throws ParseError
     */
    public function parseFromString(string $xml): ItemParseResult
    {
        try {
            $document = $this->xmlReader->read($xml);
        } catch (XmlParsingException $exception) {
            throw new ParseError('Invalid assessment item XML: ' . $exception->getMessage(), 0, $exception);
        }

        $element = $document->documentElement;
        if (!$element instanceof DOMElement) {
            throw new ParseError('Assessment item XML has no document element'); // @codeCoverageIgnore
        }

        return $this->parse($element);
    }

    /**
     * Parse an assessment item element into its model plus the warnings for any
     * construct that could not be represented (see {@see ItemParseResult}).
     */
    public function parse(DOMElement $element): ItemParseResult
    {
        $this->validateTag($element, AssessmentItem::qtiTagName());
        $warnings = new StringCollection();

        $identifierValue = $element->getAttribute('identifier');
        $identifier = AssessmentItemId::fromString($identifierValue ?: 'item-' . uniqid());
        $title = $element->getAttribute('title') ?: '';

        $responseDeclarations = new ResponseDeclarationCollection();
        $outcomeDeclarations = new OutcomeDeclarationCollection();
        $itemBody = null;
        $responseProcessing = null;
        $stylesheet = null;
        $stylesheetCount = 0;
        $modalFeedbacks = [];

        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === ResponseDeclaration::qtiTagName()) {
                $responseDeclarations->add($this->responseDeclarationParser->parse($child));
            } elseif ($child->nodeName === OutcomeDeclaration::qtiTagName()) {
                $outcomeDeclarations->add($this->outcomeDeclarationParser->parse($child));
            } elseif ($child->nodeName === ItemBody::qtiTagName()) {
                $itemBody = $this->itemBodyParser->parse($child);
            } elseif ($child->nodeName === ResponseProcessing::qtiTagName()) {
                $responseProcessing = $this->responseProcessingParser->parse($child);
            } elseif ($child->nodeName === Stylesheet::qtiTagName()) {
                $stylesheet = $this->stylesheetParser->parse($child);
                $stylesheetCount++;
            } elseif ($child->nodeName === ModalFeedback::qtiTagName()) {
                $modalFeedbacks[] = $this->modalFeedbackParser->parse($child);
            }
        }

        if ($itemBody === null) {
            throw new ParseError('AssessmentItem must contain an itemBody');
        }

        if ($stylesheetCount > 1) {
            $warnings->add('Assessment item keeps only one <qti-stylesheet>; the others are dropped');
        }

        $this->warnUnconsumed(
            $element,
            ['identifier', 'title', 'adaptive', 'time-dependent', 'xml:lang'],
            [
                ResponseDeclaration::qtiTagName(),
                OutcomeDeclaration::qtiTagName(),
                ItemBody::qtiTagName(),
                ResponseProcessing::qtiTagName(),
                Stylesheet::qtiTagName(),
                ModalFeedback::qtiTagName(),
            ],
            $warnings,
        );

        $item = new AssessmentItem(
            $identifier,
            $itemBody,
            $responseDeclarations,
            $outcomeDeclarations,
            $responseProcessing,
            $title,
            $stylesheet,
            $modalFeedbacks,
            timeDependent: $element->getAttribute('time-dependent') === 'true',
            adaptive: $element->getAttribute('adaptive') === 'true',
            language: $element->getAttribute('xml:lang') ?: 'nl-NL',
        );

        return new ItemParseResult($item, $warnings);
    }
}
