<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Exception;

final class NotFoundException extends DomainError
{
    public function __construct(
        private readonly string $customMessage,
        private readonly string $errorCode,
    ) {
        parent::__construct($this->errorMessage());
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    protected function errorMessage(): string
    {
        return $this->customMessage;
    }
}
