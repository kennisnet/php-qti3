<?php

declare(strict_types=1);

namespace Qti3\Package\Exception;

use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Exception\DomainError;
use Qti3\Shared\Exception\ErrorType;
use Qti3\Shared\Exception\HasValidationErrors;

/**
 * Thrown when item content references a resource that cannot be resolved
 * against the package: a relative path not present in the package, or a path
 * that escapes it. Each offending reference is listed in the validation errors.
 */
final class InvalidResourceReferenceException extends DomainError implements HasValidationErrors
{
    public function __construct(
        private readonly StringCollection $validationErrors,
    ) {
        parent::__construct($this->errorMessage());
    }

    public function errorCode(): string
    {
        return 'invalid_resource_reference';
    }

    public function errorType(): ErrorType
    {
        return ErrorType::VALIDATION;
    }

    protected function errorMessage(): string
    {
        return 'Item references one or more resources that are not present in the package';
    }

    public function validationErrors(): StringCollection
    {
        return $this->validationErrors;
    }
}
