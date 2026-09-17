<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Model\OutcomeProcessing;

use Qti3\Shared\Model\QtiElement;

class OutcomeElse extends QtiElement
{
    /** @var list<IOutcomeProcessingElement> */
    public readonly array $elements;

    /** The rules run when no branch before it held; the schema allows any number, including none. */
    public function __construct(
        IOutcomeProcessingElement ...$elements,
    ) {
        $this->elements = array_values($elements);
    }

    public function children(): array
    {
        return $this->elements;
    }
}
