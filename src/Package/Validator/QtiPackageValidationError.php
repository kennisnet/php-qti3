<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Exception\DomainError;
use App\SharedKernel\Domain\Exception\ErrorType;
use App\SharedKernel\Domain\Exception\HasValidationErrors;
use App\SharedKernel\Domain\StringCollection;

class QtiPackageValidationError extends DomainError implements HasValidationErrors
{
    public function __construct(
        public StringCollection $validationErrors,
        public string $messagePrefix = 'Validation errors',
    ) {
        parent::__construct($this->errorMessage());
    }

    public function errorCode(): string
    {
        return 'validation_errors';
    }

    protected function errorMessage(): string
    {
        return $this->messagePrefix . ': ' . $this->validationErrors->join(', ');
    }

    public function errorType(): ErrorType
    {
        return ErrorType::VALIDATION;
    }

    public function validationErrors(): StringCollection
    {
        return $this->validationErrors;
    }
}
