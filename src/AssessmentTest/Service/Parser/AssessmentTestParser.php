<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service\Parser;

use Qti3\AssessmentItem\Model\RubricBlock\RubricBlock;
use Qti3\AssessmentItem\Model\RubricBlock\RubricBlockCollection;
use Qti3\AssessmentItem\Service\Parser\AbstractParser;
use Qti3\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use Qti3\AssessmentItem\Service\Parser\RubricBlockParser;
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
        private readonly TestPartParser $testPartParser,
        private readonly RubricBlockParser $rubricBlockParser,
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
        $rubricBlocks = new RubricBlockCollection();
        $testParts = new TestPartCollection();

        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === OutcomeDeclaration::qtiTagName()) {
                $outcomeDeclarations->add($this->outcomeDeclarationParser->parse($child));
            } elseif ($child->nodeName === RubricBlock::qtiTagName()) {
                $rubricBlocks->add($this->rubricBlockParser->parse($child));
            } elseif ($child->nodeName === TestPart::qtiTagName()) {
                $testParts->add($this->testPartParser->parse($child, $warnings));
            }
        }

        $this->warnUnconsumed(
            $element,
            ['identifier', 'title'],
            [OutcomeDeclaration::qtiTagName(), RubricBlock::qtiTagName(), TestPart::qtiTagName()],
            $warnings,
        );

        $test = new AssessmentTest(
            $identifier,
            $outcomeDeclarations,
            $testParts,
            $title,
            rubricBlocks: $rubricBlocks
        );

        return new TestParseResult($test, $warnings);
    }
}
