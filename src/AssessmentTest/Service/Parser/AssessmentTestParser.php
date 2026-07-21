<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service\Parser;

use Qti3\AssessmentItem\Service\Parser\AbstractParser;
use Qti3\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\AssessmentTest\Model\AssessmentTestId;
use Qti3\AssessmentTest\Model\TestPart\TestPart;
use Qti3\AssessmentTest\Model\TestPart\TestPartCollection;
use Qti3\AssessmentTest\Service\TestParseResult;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclaration;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use DOMElement;

class AssessmentTestParser extends AbstractParser
{
    public function __construct(
        private readonly OutcomeDeclarationParser $outcomeDeclarationParser,
        private readonly TestPartParser $testPartParser
    ) {}

    /**
     * Parse an assessment test element into its model plus the warnings for any
     * construct that could not be represented (see {@see TestParseResult}).
     */
    public function parse(DOMElement $element): TestParseResult
    {
        $this->validateTag($element, AssessmentTest::qtiTagName());
        $warnings = new StringCollection();

        $identifierValue = $element->getAttribute('identifier');

        $identifier = AssessmentTestId::fromString($identifierValue ?: 'test-' . uniqid());

        $title = $element->getAttribute('title') ?: null;

        $outcomeDeclarations = new OutcomeDeclarationCollection();
        $testParts = new TestPartCollection();

        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === OutcomeDeclaration::qtiTagName()) {
                $outcomeDeclarations->add($this->outcomeDeclarationParser->parse($child));
            } elseif ($child->nodeName === TestPart::qtiTagName()) {
                $testParts->add($this->testPartParser->parse($child, $warnings));
            }
        }

        $this->warnUnconsumed(
            $element,
            ['identifier', 'title'],
            [OutcomeDeclaration::qtiTagName(), TestPart::qtiTagName()],
            $warnings,
        );

        $test = new AssessmentTest(
            $identifier,
            $outcomeDeclarations,
            $testParts,
            $title
        );

        return new TestParseResult($test, $warnings);
    }
}
