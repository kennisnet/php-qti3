<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service\Parser;

use Qti3\AssessmentItem\Service\Parser\AbstractParser;
use Qti3\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use Qti3\AssessmentTest\Model\ItemRef\AssessmentItemRefCollection;
use Qti3\AssessmentTest\Model\Section\AssessmentSection;
use Qti3\AssessmentTest\Model\Section\Ordering;
use Qti3\AssessmentTest\Model\Section\Selection;
use Qti3\Shared\Collection\StringCollection;
use DOMElement;

class AssessmentSectionParser extends AbstractParser
{
    public function __construct(
        private readonly AssessmentItemRefParser $itemRefParser
    ) {}

    public function parse(DOMElement $element, ?StringCollection $warnings = null): AssessmentSection
    {
        $this->validateTag($element, AssessmentSection::qtiTagName());
        $warnings ??= new StringCollection();

        $identifier = $element->getAttribute('identifier');
        $title = $element->getAttribute('title');
        $visible = $element->getAttribute('visible') !== 'false';

        $selection = null;
        $ordering = null;
        $assessmentItemRefs = new AssessmentItemRefCollection();

        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === Selection::qtiTagName()) {
                $selection = new Selection(
                    (int) $child->getAttribute('select'),
                    $child->getAttribute('with-replacement') === 'true'
                );
                $this->warnUnconsumed($child, ['select', 'with-replacement'], [], $warnings);
            } elseif ($child->nodeName === Ordering::qtiTagName()) {
                $ordering = new Ordering(
                    $child->getAttribute('shuffle') === 'true'
                );
                $this->warnUnconsumed($child, ['shuffle'], [], $warnings);
            } elseif ($child->nodeName === AssessmentItemRef::qtiTagName()) {
                $assessmentItemRefs->add($this->itemRefParser->parse($child, $warnings));
            }
        }

        $this->warnUnconsumed(
            $element,
            ['identifier', 'title', 'visible'],
            [Selection::qtiTagName(), Ordering::qtiTagName(), AssessmentItemRef::qtiTagName()],
            $warnings,
        );

        return new AssessmentSection(
            $identifier,
            $title,
            $assessmentItemRefs,
            $selection,
            $ordering,
            $visible
        );
    }
}
