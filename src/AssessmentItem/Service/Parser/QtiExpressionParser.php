<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\AbstractQtiExpression;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Contains;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Correct;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Delete;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Divide;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Equal;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Gt;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Gte;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Index;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IndexExpression;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IntegerDivide;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IntegerModulus;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IsNull;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Lt;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Lte;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Max;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Member;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Min;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Multiple;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Power;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Product;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\qtiAnd;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\qtiMatch;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\qtiNot;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\qtiOr;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Round;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\RoundTo;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Substring;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Subtract;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Sum;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\MapResponse;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\MapResponsePoint;
use DOMElement;

class QtiExpressionParser extends AbstractParser
{
    public function parse(DOMElement $element): AbstractQtiExpression
    {
        $tagName = $element->nodeName;
        $children = $this->getChildren($element);

        if ($tagName === Correct::qtiTagName()) {
            return new Correct($element->getAttribute('identifier'));
        }

        if ($tagName === Lte::qtiTagName()) {
            return new Lte(
                $this->parse($children[0]),
                $this->parse($children[1])
            );
        }

        if ($tagName === Lt::qtiTagName()) {
            return new Lt(
                $this->parse($children[0]),
                $this->parse($children[1])
            );
        }

        if ($tagName === Gte::qtiTagName()) {
            return new Gte(
                $this->parse($children[0]),
                $this->parse($children[1])
            );
        }

        if ($tagName === Gt::qtiTagName()) {
            return new Gt(
                $this->parse($children[0]),
                $this->parse($children[1])
            );
        }

        if ($tagName === qtiMatch::qtiTagName()) {
            return new qtiMatch(
                $this->parse($children[0]),
                $this->parse($children[1])
            );
        }

        if ($tagName === Equal::qtiTagName()) {
            return new Equal(
                $this->parse($children[0]),
                $this->parse($children[1])
            );
        }

        if ($tagName === Divide::qtiTagName()) {
            return new Divide(
                $this->parse($children[0]),
                $this->parse($children[1])
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

            return new BaseValue(
                BaseType::from($element->getAttribute('base-type')),
                $value
            );
        }

        if ($tagName === Variable::qtiTagName()) {
            return new Variable($element->getAttribute('identifier'));
        }

        if ($tagName === IsNull::qtiTagName()) {
            $variable = $children[0];
            $this->validateTag($variable, Variable::qtiTagName());
            return new IsNull(new Variable($variable->getAttribute('identifier')));
        }

        if ($tagName === Sum::qtiTagName()) {
            return new Sum(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children
                )
            );
        }

        if ($tagName === Product::qtiTagName()) {
            return new Product(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children
                )
            );
        }

        if ($tagName === Multiple::qtiTagName()) {
            return new Multiple(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children
                )
            );
        }

        if ($tagName === qtiAnd::qtiTagName()) {
            return new qtiAnd(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children
                )
            );
        }

        if ($tagName === qtiOr::qtiTagName()) {
            return new qtiOr(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children
                )
            );
        }

        if ($tagName === MapResponsePoint::qtiTagName()) {
            return new MapResponsePoint($element->getAttribute('identifier'));
        }

        if ($tagName === Member::qtiTagName()) {
            return new Member(
                $this->parse($children[0]),
                $this->parse($children[1]),
            );
        }

        if ($tagName === qtiNot::qtiTagName()) {
            return new qtiNot($this->parse($children[0]));
        }

        if ($tagName === Contains::qtiTagName()) {
            return new Contains(
                $this->parse($children[0]),
                $this->parse($children[1])
            );
        }

        if ($tagName === Substring::qtiTagName()) {
            return new Substring(
                $this->parse($children[0]),
                $this->parse($children[1]),
                $element->getAttribute('case-sensitive') === 'true',
            );
        }

        if ($tagName === Subtract::qtiTagName()) {
            return new Subtract(
                $this->parse($children[0]),
                $this->parse($children[1])
            );
        }

        if ($tagName === Power::qtiTagName()) {
            return new Power(
                $this->parse($children[0]),
                $this->parse($children[1])
            );
        }

        if ($tagName === Round::qtiTagName()) {
            $roundingMode = $element->getAttribute('rounding-mode') ?: 'nearest';
            return new Round(
                $this->parse($children[0]),
                $roundingMode
            );
        }

        if ($tagName === RoundTo::qtiTagName()) {
            $roundingMode = $element->getAttribute('rounding-mode') ?: 'nearest';
            return new RoundTo(
                $this->parse($children[0]),
                $this->parse($children[1]),
                $roundingMode
            );
        }

        if ($tagName === IntegerDivide::qtiTagName()) {
            return new IntegerDivide(
                $this->parse($children[0]),
                $this->parse($children[1])
            );
        }

        if ($tagName === IntegerModulus::qtiTagName()) {
            return new IntegerModulus(
                $this->parse($children[0]),
                $this->parse($children[1])
            );
        }

        if ($tagName === Min::qtiTagName()) {
            return new Min(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children
                )
            );
        }

        if ($tagName === Max::qtiTagName()) {
            return new Max(
                array_map(
                    fn($child): AbstractQtiExpression => $this->parse($child),
                    $children
                )
            );
        }

        if ($tagName === Index::qtiTagName()) {
            return new Index(
                $this->parse($children[0]),
                new IndexExpression($element->getAttribute('n'))
            );
        }

        if ($tagName === Delete::qtiTagName()) {
            return new Delete(
                $this->parse($children[0]),
                $this->parse($children[1])
            );
        }

        throw new ParseError("Unknown qti expression tag $tagName");
    }
}
