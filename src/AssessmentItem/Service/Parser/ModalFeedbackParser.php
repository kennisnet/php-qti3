<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use DOMElement;
use Qti3\AssessmentItem\Model\Feedback\ModalFeedback;
use Qti3\AssessmentItem\Model\Feedback\Visibility;
use Qti3\AssessmentItem\Model\Stylesheet\Stylesheet;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Html\ContentNodeParser;
use Qti3\Shared\Model\ContentBody;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\IXmlElement;

class ModalFeedbackParser extends AbstractParser
{
    public function __construct(private readonly StylesheetParser $stylesheetParser) {}

    public function parse(DOMElement $element, ?StringCollection $warnings = null): IXmlElement
    {
        $this->validateTag($element, ModalFeedback::qtiTagName());

        $identifier = $element->getAttribute('identifier');
        $outcomeIdentifier = $element->getAttribute('outcome-identifier') ?: 'FEEDBACK';
        $showHide = $element->getAttribute('show-hide') ?: Visibility::SHOW->value;
        $visibility = Visibility::from($showHide);
        $title = $element->getAttribute('title') ?: null;

        $stylesheets = [];
        $contentBody = null;

        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === Stylesheet::qtiTagName()) {
                $stylesheets[] = $this->stylesheetParser->parse($child);
                continue;
            }
            if ($child->nodeName === 'qti-content-body') {
                $contentBody = $this->parseContentBody($child, $warnings);
                continue;
            }
            // qti-catalog-info: not currently modeled; ignoring
        }

        return new ModalFeedback($identifier, $outcomeIdentifier, $visibility, $title, $contentBody, $stylesheets);
    }

    private function parseContentBody(DOMElement $element, ?StringCollection $warnings): ContentBody
    {
        $content = new ContentNodeCollection();
        foreach ($element->childNodes as $child) {
            $node = ContentNodeParser::parse($child);
            if ($node === null) {
                continue;
            }

            $this->warnUnlistedDirectChild($child, $node, ContentBody::allowsAsDirectChild(...), $warnings);
            $content->add($node);
        }

        return new ContentBody($content);
    }
}
