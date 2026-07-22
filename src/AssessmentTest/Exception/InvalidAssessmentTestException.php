<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Exception;

use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Exception\DomainError;
use Qti3\Shared\Exception\ErrorType;
use Qti3\Shared\Exception\HasValidationErrors;

/**
 * The assessment test is structurally invalid for the requested operation,
 * e.g. it has no section to hold item refs or contains duplicate item refs.
 */
final class InvalidAssessmentTestException extends DomainError implements HasValidationErrors
{
    public function __construct(
        private readonly StringCollection $validationErrors,
    ) {
        parent::__construct($this->errorMessage());
    }

    public function errorCode(): string
    {
        return 'invalid_assessment_test';
    }

    public function errorType(): ErrorType
    {
        return ErrorType::VALIDATION;
    }

    protected function errorMessage(): string
    {
        return 'Assessment test is invalid';
    }

    public function validationErrors(): StringCollection
    {
        return $this->validationErrors;
    }
}
