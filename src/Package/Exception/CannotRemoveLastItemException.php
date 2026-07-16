<?php

declare(strict_types=1);

namespace Qti3\Package\Exception;

use Qti3\Shared\Exception\DomainError;
use Qti3\Shared\Exception\ErrorType;

final class CannotRemoveLastItemException extends DomainError
{
    public function __construct(
        private readonly string $identifier,
    ) {
        parent::__construct($this->errorMessage());
    }

    public function errorCode(): string
    {
        return 'cannot_remove_last_item';
    }

    public function errorType(): ErrorType
    {
        return ErrorType::VALIDATION;
    }

    protected function errorMessage(): string
    {
        return sprintf('Item %s is the last item in the package and cannot be removed.', $this->identifier);
    }
}
