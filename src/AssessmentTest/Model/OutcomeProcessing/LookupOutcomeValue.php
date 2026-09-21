<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Model\OutcomeProcessing;

use Qti3\Shared\Model\Processing\AbstractQtiExpression;
use Qti3\Shared\Model\QtiElement;

/** Sets an outcome from the lookup table in its outcome declaration. */
class LookupOutcomeValue extends QtiElement implements IOutcomeProcessingElement
{
    public function __construct(
        public readonly string $identifier,
        public readonly AbstractQtiExpression $value,
    ) {}

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
        ];
    }

    public function children(): array
    {
        return [
            $this->value,
        ];
    }
}
