<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\AbstractQtiExpression;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\BaseValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Correct;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IProcessingElement;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IsNull;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\qtiMatch;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;

class ResponseCondition extends QtiElement implements IProcessingElement
{
    public function __construct(
        public readonly ResponseIf $if,
        /** @var array<int, ResponseElseIf> */
        public readonly array $elseIfs = [],
        public readonly ?ResponseElse $else = null,
    ) {}

    public static function matchCorrect(
        float $scoreCorrect = 1.0,
        float $scoreIncorrect = 0.0,
    ): self {
        return new ResponseCondition(
            if: new ResponseIf(
                new qtiMatch(
                    new Variable('RESPONSE'),
                    new Correct('RESPONSE'),
                ),
                [new SetOutcomeValue('SCORE', new BaseValue(BaseType::FLOAT, $scoreCorrect))],
            ),
            else: new ResponseElse(
                [new SetOutcomeValue('SCORE', new BaseValue(BaseType::FLOAT, $scoreIncorrect))],
            ),
        );
    }

    public static function mapResponse(): self
    {
        return new ResponseCondition(
            if: new ResponseIf(
                new IsNull(new Variable('RESPONSE')),
                [new SetOutcomeValue('SCORE', new BaseValue(BaseType::FLOAT, 0.0))],
            ),
            else: new ResponseElse(
                [new SetOutcomeValue('SCORE', new MapResponse('RESPONSE'))],
            ),
        );
    }

    public static function mapResponsePoint(): self
    {
        return new ResponseCondition(
            if: new ResponseIf(
                new IsNull(new Variable('RESPONSE')),
                [new SetOutcomeValue('SCORE', new BaseValue(BaseType::FLOAT, 0.0))],
            ),
            else: new ResponseElse(
                [new SetOutcomeValue('SCORE', new MapResponsePoint('RESPONSE'))],
            ),
        );
    }

    public static function feedbackCorrect(AbstractQtiExpression $correctCondition): self
    {
        return new ResponseCondition(
            if: new ResponseIf(
                $correctCondition,
                [new SetOutcomeValue('FEEDBACK', new BaseValue(BaseType::IDENTIFIER, 'correct'))],
            ),
            else: new ResponseElse(
                [new SetOutcomeValue('FEEDBACK', new BaseValue(BaseType::IDENTIFIER, 'incorrect'))],
            ),
        );
    }

    public static function feedbackBoolean(): self
    {
        return new ResponseCondition(
            if: new ResponseIf(
                new IsNull(new Variable('SCORE')),
                [new SetOutcomeValue('FEEDBACK', new BaseValue(BaseType::IDENTIFIER, 'false'))],
            ),
            else: new ResponseElse(
                [new SetOutcomeValue('FEEDBACK', new BaseValue(BaseType::IDENTIFIER, 'true'))],
            ),
        );
    }

    public function children(): array
    {
        return [
            $this->if,
            ...$this->elseIfs,
            $this->else,
        ];
    }

    public function processResponses(ItemState $state): void
    {
        if ($this->if->condition->evaluate($state)) {
            $this->if->processResponses($state);
            return;
        }
        foreach ($this->elseIfs as $elseIf) {
            if ($elseIf->condition->evaluate($state)) {
                $elseIf->processResponses($state);
                return;
            }
        }
        if ($this->else) {
            $this->else->processResponses($state);
        }
    }

    public function validate(ItemState $itemState): StringCollection
    {
        $errors = $this->if->validate($itemState);

        foreach ($this->elseIfs as $elseIf) {
            $errors = $errors->mergeWith($elseIf->validate($itemState));
        }

        if ($this->else) {
            $errors = $errors->mergeWith($this->else->validate($itemState));
        }

        return $errors;
    }
}
