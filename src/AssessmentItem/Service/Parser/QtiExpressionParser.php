<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use Qti3\Shared\Model\BaseType;
use Qti3\Shared\Model\Processing\AbstractQtiExpression;
use Qti3\Shared\Model\Processing\BaseValue;
use Qti3\Shared\Model\Processing\Contains;
use Qti3\Shared\Model\Processing\Correct;
use Qti3\Shared\Model\Processing\Delete;
use Qti3\Shared\Model\Processing\Divide;
use Qti3\Shared\Model\Processing\Equal;
use Qti3\Shared\Model\Processing\Gt;
use Qti3\Shared\Model\Processing\Gte;
use Qti3\Shared\Model\Processing\Index;
use Qti3\Shared\Model\Processing\IndexExpression;
use Qti3\Shared\Model\Processing\IntegerDivide;
use Qti3\Shared\Model\Processing\IntegerModulus;
use Qti3\Shared\Model\Processing\IsNull;
use Qti3\Shared\Model\Processing\Lt;
use Qti3\Shared\Model\Processing\Lte;
use Qti3\Shared\Model\Processing\Max;
use Qti3\Shared\Model\Processing\Member;
use Qti3\Shared\Model\Processing\Min;
use Qti3\Shared\Model\Processing\Multiple;
use Qti3\Shared\Model\Processing\Ordered;
use Qti3\Shared\Model\Processing\Power;
use Qti3\Shared\Model\Processing\Product;
use Qti3\Shared\Model\Processing\qtiAnd;
use Qti3\Shared\Model\Processing\qtiMatch;
use Qti3\Shared\Model\Processing\qtiNot;
use Qti3\Shared\Model\Processing\qtiOr;
use Qti3\Shared\Model\Processing\Round;
use Qti3\Shared\Model\Processing\RoundTo;
use Qti3\Shared\Model\Processing\Substring;
use Qti3\Shared\Model\Processing\Subtract;
use Qti3\Shared\Model\Processing\Sum;
use Qti3\Shared\Model\Processing\Variable;
use Qti3\AssessmentItem\Model\ResponseProcessing\MapResponse;
use Qti3\AssessmentItem\Model\ResponseProcessing\MapResponsePoint;
use Qti3\AssessmentTest\Model\OutcomeProcessing\TestVariables;
use DOMElement;

class QtiExpressionParser extends AbstractParser
{
    /**
     * The operand at `$index`, or a ParseError when the expression carries fewer than its
     * operator needs. Reading it straight off the array raises a PHP warning first and a
     * TypeError after it, and neither lets a caller treat this as a malformed expression.
     *
     * @param array<int, DOMElement> $children
     */
    private function operand(array $children, int $index, string $tagName): DOMElement
    {
        return $children[$index] ?? throw new ParseError(sprintf(
            '<%s> needs at least %d operands, got %d',
            $tagName,
            $index + 1,
            count($children),
        ));
    }

    public function parse(DOMElement $element): AbstractQtiExpression
    {
        $tagName = strtolower($element->nodeName);
        $children = $this->getChildren($element);

        if ($tagName === Correct::qtiTagName()) {
            return new Correct($element->getAttribute('identifier'));
        }

        if ($tagName === Lte::qtiTagName()) {
            return new Lte(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === Lt::qtiTagName()) {
            return new Lt(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === Gte::qtiTagName()) {
            return new Gte(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === Gt::qtiTagName()) {
            return new Gt(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === qtiMatch::qtiTagName()) {
            return new qtiMatch(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === Equal::qtiTagName()) {
            return new Equal(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === Divide::qtiTagName()) {
            return new Divide(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === MapResponse::qtiTagName()) {
            return new MapResponse($element->getAttribute('identifier'));
        }

        if ($tagName === BaseValue::qtiTagName()) {
            $value = $element->nodeValue;

            if ($value === null) {
                throw new ParseError('Empty base value'); // @codeCoverageIgnore
            }

            $baseType = $element->getAttribute('base-type');

            return new BaseValue(
                BaseType::tryFrom($baseType) ?? throw new ParseError(sprintf('<%s> has unknown base-type "%s"', $tagName, $baseType)),
                $value,
            );
        }

        if ($tagName === Variable::qtiTagName()) {
            return new Variable($element->getAttribute('identifier'));
        }

        if ($tagName === TestVariables::qtiTagName()) {
            return new TestVariables(
                $element->getAttribute('variable-identifier'),
                $element->getAttribute('include-category') ?: null,
                $element->getAttribute('section-identifier') ?: null,
                $element->getAttribute('exclude-category') ?: null,
                $element->getAttribute('weight-identifier') ?: null,
                $element->getAttribute('base-type') ?: null,
            );
        }

        if ($tagName === IsNull::qtiTagName()) {
            $variable = $children[0] ?? null;
            $this->validateTag($variable, Variable::qtiTagName());
            return new IsNull(new Variable($variable->getAttribute('identifier')));
        }

        if ($tagName === Sum::qtiTagName()) {
            return new Sum(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children,
                ),
            );
        }

        if ($tagName === Product::qtiTagName()) {
            return new Product(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children,
                ),
            );
        }

        if ($tagName === Multiple::qtiTagName()) {
            return new Multiple(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children,
                ),
            );
        }

        if ($tagName === qtiAnd::qtiTagName()) {
            return new qtiAnd(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children,
                ),
            );
        }

        if ($tagName === qtiOr::qtiTagName()) {
            return new qtiOr(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children,
                ),
            );
        }

        if ($tagName === MapResponsePoint::qtiTagName()) {
            return new MapResponsePoint($element->getAttribute('identifier'));
        }

        if ($tagName === Member::qtiTagName()) {
            return new Member(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === qtiNot::qtiTagName()) {
            return new qtiNot($this->parse($this->operand($children, 0, $tagName)));
        }

        if ($tagName === Contains::qtiTagName()) {
            return new Contains(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === Substring::qtiTagName()) {
            return new Substring(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
                $element->getAttribute('case-sensitive') === 'true',
            );
        }

        if ($tagName === Subtract::qtiTagName()) {
            return new Subtract(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === Power::qtiTagName()) {
            return new Power(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === Round::qtiTagName()) {
            $roundingMode = $element->getAttribute('rounding-mode') ?: 'nearest';
            return new Round(
                $this->parse($this->operand($children, 0, $tagName)),
                $roundingMode,
            );
        }

        if ($tagName === RoundTo::qtiTagName()) {
            $roundingMode = $element->getAttribute('rounding-mode') ?: 'nearest';
            return new RoundTo(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
                $roundingMode,
            );
        }

        if ($tagName === IntegerDivide::qtiTagName()) {
            return new IntegerDivide(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === IntegerModulus::qtiTagName()) {
            return new IntegerModulus(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === Min::qtiTagName()) {
            return new Min(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children,
                ),
            );
        }

        if ($tagName === Max::qtiTagName()) {
            return new Max(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children,
                ),
            );
        }

        if ($tagName === Index::qtiTagName()) {
            return new Index(
                $this->parse($this->operand($children, 0, $tagName)),
                new IndexExpression($element->getAttribute('n')),
            );
        }

        if ($tagName === Delete::qtiTagName()) {
            return new Delete(
                $this->parse($this->operand($children, 0, $tagName)),
                $this->parse($this->operand($children, 1, $tagName)),
            );
        }

        if ($tagName === Ordered::qtiTagName()) {
            return new Ordered(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children,
                ),
            );
        }

        throw new ParseError("Unknown qti expression tag $tagName");
    }
}
