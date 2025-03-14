<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape;

use InvalidArgumentException;
use Stringable;

readonly class Coordinate implements Stringable
{
    public function __construct(
        private string $value
    ) {
        if (!preg_match('/^\d+%?$/', (string) $value)) {
            throw new InvalidArgumentException(sprintf('Invalid coordinate value: %s', $value));
        }
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
