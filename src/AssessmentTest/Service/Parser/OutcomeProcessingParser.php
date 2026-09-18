<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service\Parser;

use DOMElement;
use Qti3\AssessmentItem\Service\Parser\AbstractParser;
use Qti3\AssessmentItem\Service\Parser\ParseError;
use Qti3\AssessmentItem\Service\Parser\QtiExpressionParser;
use Qti3\AssessmentTest\Model\OutcomeProcessing\ExitTest;
use Qti3\AssessmentTest\Model\OutcomeProcessing\IOutcomeProcessingElement;
use Qti3\AssessmentTest\Model\OutcomeProcessing\LookupOutcomeValue;
use Qti3\AssessmentTest\Model\OutcomeProcessing\OutcomeCondition;
use Qti3\AssessmentTest\Model\OutcomeProcessing\OutcomeElse;
use Qti3\AssessmentTest\Model\OutcomeProcessing\OutcomeElseIf;
use Qti3\AssessmentTest\Model\OutcomeProcessing\OutcomeIf;
use Qti3\AssessmentTest\Model\OutcomeProcessing\OutcomeProcessing;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Model\Processing\AbstractQtiExpression;
use Qti3\Shared\Model\Processing\SetOutcomeValue;
use TypeError;
use ValueError;

/**
 * Parses the test-level `<qti-outcome-processing>` into its model.
 *
 * A top-level rule the model cannot hold is dropped on its own, with a warning.
 * Inside a condition the whole <qti-outcome-condition> goes: keeping the other
 * branches would silently change what the test scores.
 */
class OutcomeProcessingParser extends AbstractParser
{
    public function __construct(
        private readonly QtiExpressionParser $expressionParser,
    ) {}

    public function parse(DOMElement $element, ?StringCollection $warnings = null): OutcomeProcessing
    {
        $this->validateTag($element, OutcomeProcessing::qtiTagName());
        $warnings ??= new StringCollection();

        $this->warnUnconsumed($element, [], $this->ruleTags(), $warnings);

        $rules = [];
        foreach ($this->getChildren($element) as $child) {
            if (!in_array($child->nodeName, $this->ruleTags(), true)) {
                continue; // already reported by warnUnconsumed()
            }

            try {
                $rules[] = $this->parseRule($child);
            } catch (ParseError | TypeError | ValueError $error) {
                // TypeError: an operator short of an operand; ValueError: an unknown base-type. Both come
                // out of QtiExpressionParser as-is, and both mean the rule cannot be held.
                $warnings->add(sprintf('%s: drops <%s>, %s', $this->locate($child), $child->nodeName, $error->getMessage()));
            }
        }

        return new OutcomeProcessing($rules);
    }

    /** @return list<string> */
    private function ruleTags(): array
    {
        return [
            SetOutcomeValue::qtiTagName(),
            LookupOutcomeValue::qtiTagName(),
            ExitTest::qtiTagName(),
            OutcomeCondition::qtiTagName(),
        ];
    }

    private function parseRule(DOMElement $element): IOutcomeProcessingElement
    {
        return match ($element->nodeName) {
            SetOutcomeValue::qtiTagName() => new SetOutcomeValue($element->getAttribute('identifier'), $this->parseSingleExpression($element)),
            LookupOutcomeValue::qtiTagName() => new LookupOutcomeValue($element->getAttribute('identifier'), $this->parseSingleExpression($element)),
            ExitTest::qtiTagName() => new ExitTest(),
            OutcomeCondition::qtiTagName() => $this->parseCondition($element),
            default => throw new ParseError(sprintf('Unknown outcome processing rule %s', $element->nodeName)),
        };
    }

    private function parseCondition(DOMElement $element): OutcomeCondition
    {
        $children = $this->getChildren($element);
        $this->validateTag($children[0] ?? null, OutcomeIf::qtiTagName());

        $if = new OutcomeIf(...$this->parseBranch($children[0]));
        $elseIfs = [];
        $else = null;

        foreach (array_slice($children, 1) as $child) {
            if ($else !== null) {
                throw new ParseError(sprintf('Unexpected <%s> after <%s>', $child->nodeName, OutcomeElse::qtiTagName()));
            }
            if ($child->nodeName === OutcomeElse::qtiTagName()) {
                $else = new OutcomeElse(...$this->parseRules($this->getChildren($child)));
                continue;
            }
            $this->validateTag($child, OutcomeElseIf::qtiTagName());
            $elseIfs[] = new OutcomeElseIf(...$this->parseBranch($child));
        }

        return new OutcomeCondition($if, $elseIfs, $else);
    }

    /**
     * The condition expression followed by the rules of an if/else-if branch.
     *
     * @return list<AbstractQtiExpression|IOutcomeProcessingElement>
     */
    private function parseBranch(DOMElement $branch): array
    {
        $children = $this->getChildren($branch);
        if ($children === []) {
            throw new ParseError(sprintf('<%s> needs a condition expression', $branch->nodeName));
        }

        return [
            $this->expressionParser->parse($children[0]),
            ...$this->parseRules(array_slice($children, 1)),
        ];
    }

    /**
     * @param array<int, DOMElement> $elements
     * @return list<IOutcomeProcessingElement>
     */
    private function parseRules(array $elements): array
    {
        return array_values(array_map(fn(DOMElement $element): IOutcomeProcessingElement => $this->parseRule($element), $elements));
    }

    private function parseSingleExpression(DOMElement $element): AbstractQtiExpression
    {
        $children = $this->getChildren($element);
        if (count($children) !== 1) {
            throw new ParseError(sprintf('<%s> needs exactly one expression, got %d', $element->nodeName, count($children)));
        }

        return $this->expressionParser->parse($children[0]);
    }
}
