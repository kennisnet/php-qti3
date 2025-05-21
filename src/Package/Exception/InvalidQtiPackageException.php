<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Exception;

use App\SharedKernel\Domain\Exception\DomainError;
use App\SharedKernel\Domain\StringCollection;

final class InvalidQtiPackageException extends DomainError
{
    public function __construct(
        public readonly StringCollection $validationErrors,
    ) {
        parent::__construct();
    }

    public function errorCode(): string
    {
        return 'invalid_qti_package';
    }

    protected function errorMessage(): string
    {
        return 'QTI package is invalid';
    }
}
