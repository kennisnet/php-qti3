<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;

class Member extends AbstractQtiExpression
{
    public function __construct(
        public readonly AbstractQtiExpression $needle,
        public readonly AbstractQtiExpression $haystack
    ) {}

    public function children(): array
    {
        return [
            $this->needle,
            $this->haystack,
        ];
    }

    public function evaluate(ItemState $state): bool
    {
        $haystackValue = $this->haystack->evaluateArray($state);

        return in_array(
            $this->needle->evaluate($state),
            $haystackValue
        );
    }

    public function getBaseType(ItemState $state): BaseType
    {
        return BaseType::BOOLEAN;
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::SINGLE;
    }

    public function validate(StringCollection $identifiers): StringCollection
    {
        return $this->needle->validate($identifiers)->mergeWith($this->haystack->validate($identifiers));
    }
}
