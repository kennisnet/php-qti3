<?php

declare(strict_types=1);

namespace Qti3\Exception;

use RuntimeException;

class NotFoundException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = '', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
