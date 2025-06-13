<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Exception\DomainError;
use App\SharedKernel\Domain\StringCollection;

class ValidationError extends DomainError
{
    public function __construct(
        public StringCollection $validationErrors,
        public string $messagePrefix = 'Validation errors'
    ) {
        parent::__construct();
    }

    public function errorCode(): string
    {
        return 'validation_errors';
    }

    protected function errorMessage(): string
    {
        return $this->messagePrefix . ': ' . $this->validationErrors->join(', ');
    }
}
