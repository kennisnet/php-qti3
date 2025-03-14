<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\SetOutcomeValue;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class ResponseElse extends QtiElement
{
    public function __construct(
        public readonly SetOutcomeValue $setOutcomeValue
    ) {}

    public function children(): array
    {
        return [
            $this->setOutcomeValue,
        ];
    }
}
