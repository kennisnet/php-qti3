<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\State;

use App\SharedKernel\Domain\Qti\Package\Validator\ValidationError;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseProcessing;
use InvalidArgumentException;

class ItemState
{
    public function __construct(
        public ResponseSet $responseSet,
        public OutcomeSet $outcomeSet,
        public ResponseProcessing $responseProcessing,
        public bool $adaptive = false,
    ) {
        $this->validate();
    }

    public function getValue(string $identifier): mixed
    {
        try {
            return $this->responseSet->getResponseValue($identifier);
        } catch (InvalidArgumentException) {
            return $this->outcomeSet->getOutcomeValue($identifier);
        }
    }

    private function validate(): void
    {
        $identifiers = $this->responseSet->responseDeclarations->getIdentifiers()->mergeWith(
            $this->outcomeSet->outcomeDeclarations->getIdentifiers()
        );

        $errors = $this->responseProcessing->validate($identifiers);

        if ($errors->count() > 0) {
            throw new ValidationError($errors, 'Validation errors in response processing');
        }
    }

}
