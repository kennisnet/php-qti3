<?php

declare(strict_types=1);

namespace Qti3\Package\Exception;

use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Exception\DomainError;
use Qti3\Shared\Exception\ErrorType;
use Qti3\Shared\Exception\HasValidationErrors;

/**
 * The package contains QTI constructs the typed models cannot represent.
 * Editing regenerates the package from the typed models, so such a package is
 * refused instead of silently losing the unsupported constructs.
 */
final class UnsupportedQtiConstructException extends DomainError implements HasValidationErrors
{
    public function __construct(
        private readonly StringCollection $validationErrors,
    ) {
        parent::__construct($this->errorMessage());
    }

    public function errorCode(): string
    {
        return 'unsupported_qti_construct';
    }

    public function errorType(): ErrorType
    {
        return ErrorType::VALIDATION;
    }

    protected function errorMessage(): string
    {
        return 'QTI package contains unsupported constructs';
    }

    public function validationErrors(): StringCollection
    {
        return $this->validationErrors;
    }
}
