<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Exception;

use App\SharedKernel\Domain\Exception\DomainError;
use App\SharedKernel\Domain\Exception\ErrorType;
use App\SharedKernel\Domain\Exception\HasValidationErrors;
use App\SharedKernel\Domain\StringCollection;

final class InvalidQtiPackageException extends DomainError implements HasValidationErrors
{
    public function __construct(
        private readonly StringCollection $validationErrors,
    ) {
        parent::__construct($this->errorMessage());
    }

    public function errorCode(): string
    {
        return 'invalid_qti_package';
    }

    public function errorType(): ErrorType
    {
        return ErrorType::VALIDATION;
    }

    protected function errorMessage(): string
    {
        return 'QTI package is invalid';
    }

    public function validationErrors(): StringCollection
    {
        return $this->validationErrors;
    }
}
