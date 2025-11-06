<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\State;

use App\SharedKernel\Domain\Qti\Package\Validator\QtiPackageValidationError;
use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseProcessing;
use App\SharedKernel\Domain\StringCollection;
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

    public function getBaseType(string $identifier): BaseType
    {
        try {
            return $this->responseSet->responseDeclarations->getByIdentifier($identifier)->baseType;
        } catch (InvalidArgumentException) {
            return $this->outcomeSet->outcomeDeclarations->getByIdentifier($identifier)->baseType;
        }
    }

    public function getCardinality(string $identifier): Cardinality
    {
        try {
            return $this->responseSet->responseDeclarations->getByIdentifier($identifier)->cardinality;
        } catch (InvalidArgumentException) {
            return $this->outcomeSet->outcomeDeclarations->getByIdentifier($identifier)->cardinality;
        }
    }

    public function getIdentifiers(): StringCollection
    {
        return $this->responseSet->responseDeclarations->getIdentifiers()->mergeWith(
            $this->outcomeSet->outcomeDeclarations->getIdentifiers(),
        );
    }

    private function validate(): void
    {
        $errors = $this->responseProcessing->validate($this);

        if ($errors->count() > 0) {
            throw new QtiPackageValidationError($errors, 'Validation errors in response processing');
        }
    }

}
