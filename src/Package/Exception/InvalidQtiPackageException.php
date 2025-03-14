<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Exception;

use App\SharedKernel\Domain\Exception\DomainError;
use App\SharedKernel\Domain\StringCollection;

class InvalidQtiPackageException extends DomainError
{
    public function __construct(string $message, public readonly StringCollection $validationErrors)
    {
        parent::__construct($message);
    }
}
