<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Exception;

use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Exception\DomainError;
use Qti3\Shared\Exception\ErrorType;
use Qti3\Shared\Exception\HasValidationErrors;

final class InvalidAssessmentItemException extends DomainError implements HasValidationErrors
{
    public function __construct(
        private readonly StringCollection $validationErrors,
    ) {
        parent::__construct($this->errorMessage());
    }

    public function errorCode(): string
    {
        return 'invalid_assessment_item';
    }

    public function errorType(): ErrorType
    {
        return ErrorType::VALIDATION;
    }

    protected function errorMessage(): string
    {
        return 'Assessment item is invalid';
    }

    public function validationErrors(): StringCollection
    {
        return $this->validationErrors;
    }
}
