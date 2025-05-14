<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\State;

use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseProcessing;
use InvalidArgumentException;

class ItemState
{
    public function __construct(
        public ResponseSet $responseSet,
        public OutcomeSet $outcomeSet,
        public ResponseProcessing $responseProcessing,
        public bool $adaptive = false,
    ) {}

    public function getValue(string $identifier): mixed
    {
        try {
            return $this->responseSet->getResponseValue($identifier);
        } catch (InvalidArgumentException) {
            return $this->outcomeSet->getOutcomeValue($identifier);
        }
    }
}
