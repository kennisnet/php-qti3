<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\AbstractQtiExpression;
use App\SharedKernel\Domain\Qti\State\ItemState;

class MapResponse extends AbstractQtiExpression
{
    public function __construct(
        public readonly string $identifier,
    ) {}

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
        ];
    }

    public function evaluate(ItemState $state): float
    {
        return $state->responseSet->mapResponse($this->identifier);
    }
}
