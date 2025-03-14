<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model;

use Stringable;
use Symfony\Component\Uid\Uuid;

class QtiPackageId implements Stringable
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
