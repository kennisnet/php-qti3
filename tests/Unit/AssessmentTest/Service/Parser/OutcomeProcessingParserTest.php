<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentTest\Service\Parser;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Service\Parser\ParseError;
use Qti3\AssessmentItem\Service\Parser\QtiExpressionParser;
use Qti3\AssessmentTest\Model\OutcomeProcessing\ExitTest;
use Qti3\AssessmentTest\Model\OutcomeProcessing\LookupOutcomeValue;
use Qti3\AssessmentTest\Model\OutcomeProcessing\OutcomeCondition;
use Qti3\AssessmentTest\Model\OutcomeProcessing\TestVariables;
use Qti3\AssessmentTest\Service\Parser\OutcomeProcessingParser;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Model\Processing\Gt;
use Qti3\Shared\Model\Processing\IsNull;
use Qti3\Shared\Model\Processing\SetOutcomeValue;
use Qti3\Shared\Model\Processing\Sum;

final class OutcomeProcessingParserTest extends TestCase
{
    private OutcomeProcessingParser $parser;

    protected function setUp(): void
    {
        $this->parser = new OutcomeProcessingParser(new QtiExpressionParser());
    }

    #[Test]
    public function parsesTopLevelRulesInDocumentOrder(): void
    {
        $warnings = new StringCollection();

        $processing = $this->parser->parse($this->element(
            '<qti-outcome-processing>'
            . '<qti-set-outcome-value identifier="SCORE"><qti-sum><qti-test-variables variable-identifier="SCORE"/></qti-sum></qti-set-outcome-value>'
            . '<qti-lookup-outcome-value identifier="GRADE"><qti-variable identifier="SCORE"/></qti-lookup-outcome-value>'
            . '<qti-exit-test/>'
            . '</qti-outcome-processing>',
        ), $warnings);

        $this->assertSame([], $warnings->all());
        $this->assertCount(3, $processing->elements);

        [$set, $lookup, $exit] = $processing->elements;
        $this->assertInstanceOf(SetOutcomeValue::class, $set);
        $this->assertSame('SCORE', $set->identifier);
        $this->assertInstanceOf(Sum::class, $set->value);
        $this->assertInstanceOf(TestVariables::class, $set->value->elements[0]);
        $this->assertInstanceOf(LookupOutcomeValue::class, $lookup);
        $this->assertSame('GRADE', $lookup->identifier);
        $this->assertInstanceOf(ExitTest::class, $exit);
    }

    #[Test]
    public function parsesAConditionWithItsBranchesAndTheirRules(): void
    {
        $processing = $this->parser->parse($this->element(
            '<qti-outcome-processing><qti-outcome-condition>'
            . '<qti-outcome-if><qti-gt><qti-variable identifier="SCORE"/><qti-base-value base-type="float">1</qti-base-value></qti-gt>'
            . '<qti-set-outcome-value identifier="A"><qti-base-value base-type="integer">1</qti-base-value></qti-set-outcome-value>'
            . '<qti-set-outcome-value identifier="B"><qti-base-value base-type="integer">2</qti-base-value></qti-set-outcome-value>'
            . '</qti-outcome-if>'
            . '<qti-outcome-else-if><qti-is-null><qti-variable identifier="X"/></qti-is-null><qti-exit-test/></qti-outcome-else-if>'
            . '<qti-outcome-else-if><qti-is-null><qti-variable identifier="Y"/></qti-is-null></qti-outcome-else-if>'
            . '<qti-outcome-else><qti-outcome-condition><qti-outcome-if><qti-is-null><qti-variable identifier="Z"/></qti-is-null></qti-outcome-if></qti-outcome-condition></qti-outcome-else>'
            . '</qti-outcome-condition></qti-outcome-processing>',
        ));

        $condition = $processing->elements[0];
        $this->assertInstanceOf(OutcomeCondition::class, $condition);

        $this->assertInstanceOf(Gt::class, $condition->if->condition);
        $this->assertCount(2, $condition->if->elements);
        $this->assertInstanceOf(SetOutcomeValue::class, $condition->if->elements[1]);
        $this->assertSame('B', $condition->if->elements[1]->identifier);

        $this->assertCount(2, $condition->elseIfs);
        $this->assertInstanceOf(IsNull::class, $condition->elseIfs[0]->condition);
        $this->assertInstanceOf(ExitTest::class, $condition->elseIfs[0]->elements[0]);
        $this->assertSame([], $condition->elseIfs[1]->elements);

        $this->assertNotNull($condition->else);
        $this->assertInstanceOf(OutcomeCondition::class, $condition->else->elements[0]);
    }

    #[Test]
    public function aRuleWithAnUnknownExpressionIsDroppedWithALocatedWarning(): void
    {
        $warnings = new StringCollection();

        $processing = $this->parser->parse($this->element(
            '<qti-outcome-processing>'
            . '<qti-set-outcome-value identifier="N"><qti-number-correct/></qti-set-outcome-value>'
            . '<qti-exit-test/>'
            . '</qti-outcome-processing>',
        ), $warnings);

        $this->assertCount(1, $processing->elements);
        $this->assertInstanceOf(ExitTest::class, $processing->elements[0]);
        $this->assertCount(1, $warnings);
        $warning = $warnings->all()[0];
        $this->assertMatchesRegularExpression('/^line \d+ at \/qti-outcome-processing\/qti-set-outcome-value\[@identifier=\'N\'\]: drops <qti-set-outcome-value>, /', $warning);
        $this->assertStringContainsString('qti-number-correct', $warning);
    }

    #[Test]
    public function anUnknownRuleIsDroppedWithAWarning(): void
    {
        $warnings = new StringCollection();

        $processing = $this->parser->parse($this->element('<qti-outcome-processing><qti-outcome-rule-ext/></qti-outcome-processing>'), $warnings);

        $this->assertSame([], $processing->elements);
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('drops unsupported element <qti-outcome-rule-ext>', $warnings->all()[0]);
    }

    #[Test]
    public function aBrokenConditionIsDroppedAsAWhole(): void
    {
        $warnings = new StringCollection();

        $processing = $this->parser->parse($this->element(
            '<qti-outcome-processing><qti-outcome-condition>'
            . '<qti-outcome-if><qti-is-null><qti-variable identifier="X"/></qti-is-null><qti-exit-test/></qti-outcome-if>'
            . '<qti-outcome-else/>'
            . '<qti-outcome-else-if><qti-is-null><qti-variable identifier="Y"/></qti-is-null></qti-outcome-else-if>'
            . '</qti-outcome-condition></qti-outcome-processing>',
        ), $warnings);

        $this->assertSame([], $processing->elements);
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('drops <qti-outcome-condition>, Unexpected <qti-outcome-else-if> after <qti-outcome-else>', $warnings->all()[0]);
    }

    #[Test]
    public function aConditionMustStartWithAnIf(): void
    {
        $warnings = new StringCollection();

        $this->parser->parse($this->element('<qti-outcome-processing><qti-outcome-condition><qti-outcome-else/></qti-outcome-condition></qti-outcome-processing>'), $warnings);

        $this->assertStringContainsString('Expected tag "qti-outcome-if", got "qti-outcome-else"', $warnings->all()[0]);
    }

    #[Test]
    public function aBranchWithoutAConditionExpressionIsReported(): void
    {
        $warnings = new StringCollection();

        $this->parser->parse($this->element('<qti-outcome-processing><qti-outcome-condition><qti-outcome-if/></qti-outcome-condition></qti-outcome-processing>'), $warnings);

        $this->assertStringContainsString('<qti-outcome-if> needs a condition expression', $warnings->all()[0]);
    }

    #[Test]
    public function aSetOutcomeValueNeedsExactlyOneExpression(): void
    {
        $warnings = new StringCollection();

        $this->parser->parse($this->element(
            '<qti-outcome-processing><qti-set-outcome-value identifier="N"><qti-variable identifier="A"/><qti-variable identifier="B"/></qti-set-outcome-value></qti-outcome-processing>',
        ), $warnings);

        $this->assertStringContainsString('<qti-set-outcome-value> needs exactly one expression, got 2', $warnings->all()[0]);
    }

    #[Test]
    public function anAttributeOnTheProcessingElementIsReported(): void
    {
        $warnings = new StringCollection();

        $this->parser->parse($this->element('<qti-outcome-processing data-x="y"/>'), $warnings);

        $this->assertStringContainsString('drops unsupported attribute "data-x"', $warnings->all()[0]);
    }

    #[Test]
    public function parsingWithoutAWarningsCollectionStillWorks(): void
    {
        $processing = $this->parser->parse($this->element('<qti-outcome-processing><qti-exit-test/><qti-unknown/></qti-outcome-processing>'));

        $this->assertCount(1, $processing->elements);
    }

    #[Test]
    public function anUnknownRuleInsideABranchDropsTheWholeCondition(): void
    {
        $warnings = new StringCollection();

        $processing = $this->parser->parse($this->element(
            '<qti-outcome-processing><qti-outcome-condition>'
            . '<qti-outcome-if><qti-is-null><qti-variable identifier="X"/></qti-is-null><qti-exit-test/></qti-outcome-if>'
            . '<qti-outcome-else><qti-outcome-rule-ext/></qti-outcome-else>'
            . '</qti-outcome-condition></qti-outcome-processing>',
        ), $warnings);

        $this->assertSame([], $processing->elements);
        $this->assertSame(1, count($warnings));
        $this->assertStringContainsString('drops <qti-outcome-condition>, Unknown outcome processing rule qti-outcome-rule-ext', $warnings->all()[0]);
    }

    #[Test]
    public function anEmptyConditionAndAForeignBranchAreReported(): void
    {
        $warnings = new StringCollection();

        $processing = $this->parser->parse($this->element(
            '<qti-outcome-processing>'
            . '<qti-outcome-condition/>'
            . '<qti-outcome-condition><qti-outcome-if><qti-is-null><qti-variable identifier="X"/></qti-is-null></qti-outcome-if><qti-outcome-foo/></qti-outcome-condition>'
            . '</qti-outcome-processing>',
        ), $warnings);

        $this->assertSame([], $processing->elements);
        $this->assertSame(2, count($warnings));
        $this->assertStringContainsString('Expected tag "qti-outcome-if", no element found', $warnings->all()[0]);
        $this->assertStringContainsString('Expected tag "qti-outcome-else-if", got "qti-outcome-foo"', $warnings->all()[1]);
    }

    #[Test]
    public function anExpressionTheParserCannotBuildIsDroppedLikeAnUnknownOne(): void
    {
        // An unknown base-type ends in a ValueError deep in QtiExpressionParser; it may not take the whole test down.
        $warnings = new StringCollection();

        $processing = $this->parser->parse($this->element(
            '<qti-outcome-processing>'
            . '<qti-set-outcome-value identifier="B"><qti-base-value base-type="bogus">1</qti-base-value></qti-set-outcome-value>'
            . '<qti-exit-test/>'
            . '</qti-outcome-processing>',
        ), $warnings);

        $this->assertCount(1, $processing->elements);
        $this->assertInstanceOf(ExitTest::class, $processing->elements[0]);
        $this->assertSame(1, count($warnings));
        $this->assertStringContainsString("[@identifier='B']: drops <qti-set-outcome-value>, \"bogus\" is not a valid backing value", $warnings->all()[0]);
    }

    #[Test]
    public function rejectsAnotherElement(): void
    {
        $this->expectException(ParseError::class);

        $this->parser->parse($this->element('<qti-response-processing/>'));
    }

    private function element(string $xml): DOMElement
    {
        $document = new DOMDocument();
        $document->loadXML($xml);
        self::assertInstanceOf(DOMElement::class, $document->documentElement);

        return $document->documentElement;
    }
}
