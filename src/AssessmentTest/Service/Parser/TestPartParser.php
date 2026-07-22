<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service\Parser;

use Qti3\AssessmentItem\Service\Parser\AbstractParser;
use Qti3\AssessmentTest\Model\Section\AssessmentSection;
use Qti3\AssessmentTest\Model\Section\AssessmentSectionCollection;
use Qti3\AssessmentTest\Model\TestPart\NavigationMode;
use Qti3\AssessmentTest\Model\TestPart\SubmissionMode;
use Qti3\AssessmentTest\Model\TestPart\TestPart;
use Qti3\Shared\Collection\StringCollection;
use DOMElement;

class TestPartParser extends AbstractParser
{
    public function __construct(
        private readonly AssessmentSectionParser $sectionParser
    ) {}

    public function parse(DOMElement $element, ?StringCollection $warnings = null): TestPart
    {
        $this->validateTag($element, TestPart::qtiTagName());
        $warnings ??= new StringCollection();

        $identifier = $element->getAttribute('identifier');
        $navigationMode = NavigationMode::from($element->getAttribute('navigation-mode'));
        $submissionMode = SubmissionMode::from($element->getAttribute('submission-mode'));

        $sections = new AssessmentSectionCollection();
        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === AssessmentSection::qtiTagName()) {
                $sections->add($this->sectionParser->parse($child, $warnings));
            }
        }

        $this->warnUnconsumed(
            $element,
            ['identifier', 'navigation-mode', 'submission-mode'],
            [AssessmentSection::qtiTagName()],
            $warnings,
        );

        return new TestPart(
            $identifier,
            $navigationMode,
            $submissionMode,
            $sections
        );
    }
}
