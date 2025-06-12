<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Exception;

use DomainException;

abstract class DomainError extends DomainException
{
    abstract public function errorCode(): string; // @codeCoverageIgnore

    abstract public function errorType(): ErrorType;
}
