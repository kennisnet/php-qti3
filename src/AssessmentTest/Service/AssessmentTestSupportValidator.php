<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service;

use DOMElement;
use Qti3\Package\Exception\UnsupportedQtiConstructException;
use Qti3\Shared\Collection\StringCollection;

/**
 * Guards the typed-model editing flow: asserts that an assessment test XML
 * document only contains constructs the {@see \Qti3\AssessmentTest\Model\AssessmentTest}
 * model and its parsers can represent. Anything outside that subset (outcome
 * processing, test feedback, rubric blocks, nested sections, time limits, ...)
 * would be silently dropped when the package is regenerated from the model,
 * so such a test is refused instead.
 *
 * The allowlists must mirror what the parsers read; extend them together with
 * the model/parsers (see AssessmentTestParser, TestPartParser,
 * AssessmentSectionParser, AssessmentItemRefParser).
 */
final class AssessmentTestSupportValidator
{
    /** @var list<string> */
    private const array TEST_CHILDREN = ['qti-outcome-declaration', 'qti-test-part'];

    /** @var list<string> */
    private const array TEST_PART_CHILDREN = ['qti-assessment-section'];

    /** @var list<string> */
    private const array SECTION_CHILDREN = ['qti-selection', 'qti-ordering', 'qti-assessment-item-ref'];

    /**
     * @throws UnsupportedQtiConstructException
     */
    public function assertSupported(DOMElement $test): void
    {
        $errors = [];

        foreach ($this->childElements($test) as $testChild) {
            if (!in_array($testChild->localName, self::TEST_CHILDREN, true)) {
                $errors[] = sprintf('Assessment test contains unsupported element <%s>', $testChild->localName);
                continue;
            }
            if ($testChild->localName === 'qti-test-part') {
                $errors = [...$errors, ...$this->testPartErrors($testChild)];
            }
        }

        if ($errors !== []) {
            throw new UnsupportedQtiConstructException(new StringCollection($errors));
        }
    }

    /**
     * @return list<string>
     */
    private function testPartErrors(DOMElement $testPart): array
    {
        $errors = [];

        foreach ($this->childElements($testPart) as $testPartChild) {
            if (!in_array($testPartChild->localName, self::TEST_PART_CHILDREN, true)) {
                $errors[] = sprintf('Test part contains unsupported element <%s>', $testPartChild->localName);
                continue;
            }
            $errors = [...$errors, ...$this->sectionErrors($testPartChild)];
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function sectionErrors(DOMElement $section): array
    {
        $errors = [];

        foreach ($this->childElements($section) as $sectionChild) {
            if ($sectionChild->localName === 'qti-assessment-section') {
                $errors[] = 'Assessment test contains nested sections';
                continue;
            }
            if (!in_array($sectionChild->localName, self::SECTION_CHILDREN, true)) {
                $errors[] = sprintf('Assessment section contains unsupported element <%s>', $sectionChild->localName);
                continue;
            }
            if ($sectionChild->localName === 'qti-assessment-item-ref' && $this->childElements($sectionChild) !== []) {
                $errors[] = sprintf('Item ref "%s" contains unsupported child elements', $sectionChild->getAttribute('identifier'));
            }
        }

        return $errors;
    }

    /**
     * @return list<DOMElement>
     */
    private function childElements(DOMElement $element): array
    {
        $children = [];
        foreach ($element->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $children[] = $childNode;
            }
        }

        return $children;
    }
}
