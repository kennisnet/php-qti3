<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Model\OutcomeProcessing;

use Qti3\Shared\Model\Processing\AbstractQtiExpression;
use Qti3\Shared\Model\QtiElement;

class OutcomeIf extends QtiElement
{
    /** @var list<IOutcomeProcessingElement> */
    public readonly array $elements;

    /** The rules run when `$condition` holds; the schema allows any number, including none. */
    public function __construct(
        public readonly AbstractQtiExpression $condition,
        IOutcomeProcessingElement ...$elements,
    ) {
        $this->elements = array_values($elements);
    }

    public function children(): array
    {
        return [
            $this->condition,
            ...$this->elements,
        ];
    }
}
