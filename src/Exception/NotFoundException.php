<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Exception;

use Throwable;

final class NotFoundException extends DomainError
{
    public function __construct(
        private readonly string $customMessage,
        private readonly string $errorCode,
        private readonly ?Throwable $previous = null,
    ) {
        parent::__construct($this->errorMessage(), previous: $this->previous);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function errorType(): ErrorType
    {
        return ErrorType::NOT_FOUND;
    }

    protected function errorMessage(): string
    {
        return $this->customMessage;
    }
}
