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
        $navigationMode = $this->parseNavigationMode($element, $warnings);
        $submissionMode = $this->parseSubmissionMode($element, $warnings);

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

    /**
     * Required by the XSD, but packages arrive unvalidated, so a missing or
     * unknown value falls back to the schema's default rather than throwing a
     * raw ValueError. The next edit writes that default back explicitly.
     */
    private function parseNavigationMode(DOMElement $element, StringCollection $warnings): NavigationMode
    {
        $raw = $element->getAttribute('navigation-mode');
        $navigationMode = NavigationMode::tryFrom($raw);
        if ($navigationMode === null) {
            $warnings->add(sprintf('%s: defaults missing or unknown navigation-mode "%s" to "linear"', $this->locate($element), $raw));
            return NavigationMode::LINEAR;
        }

        return $navigationMode;
    }

    private function parseSubmissionMode(DOMElement $element, StringCollection $warnings): SubmissionMode
    {
        $raw = $element->getAttribute('submission-mode');
        $submissionMode = SubmissionMode::tryFrom($raw);
        if ($submissionMode === null) {
            $warnings->add(sprintf('%s: defaults missing or unknown submission-mode "%s" to "individual"', $this->locate($element), $raw));
            return SubmissionMode::INDIVIDUAL;
        }

        return $submissionMode;
    }
}
