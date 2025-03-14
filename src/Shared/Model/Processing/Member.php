<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class Member extends QtiElement implements IQtiExpression
{
    public function __construct(
        public readonly Variable $variable,
        public readonly Correct $set
    ) {}

    public function children(): array
    {
        return [
            $this->variable,
            $this->set,
        ];
    }
}
