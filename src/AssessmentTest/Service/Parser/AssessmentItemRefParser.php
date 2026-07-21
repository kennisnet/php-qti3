<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service\Parser;

use Qti3\AssessmentItem\Model\AssessmentItemId;
use Qti3\AssessmentItem\Service\Parser\AbstractParser;
use Qti3\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use Qti3\Shared\Collection\StringCollection;
use DOMElement;

class AssessmentItemRefParser extends AbstractParser
{
    public function parse(DOMElement $element, ?StringCollection $warnings = null): AssessmentItemRef
    {
        $this->validateTag($element, AssessmentItemRef::qtiTagName());
        $warnings ??= new StringCollection();

        $identifier = $element->getAttribute('identifier');
        $href = $element->getAttribute('href');
        $category = $element->getAttribute('category') ?: null;

        $this->warnUnconsumed($element, ['identifier', 'href', 'category'], [], $warnings);

        return new AssessmentItemRef(
            AssessmentItemId::fromString($identifier),
            $href,
            $category
        );
    }
}
