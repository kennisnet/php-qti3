<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model;

use InvalidArgumentException;
use Stringable;
use Symfony\Component\Uid\Uuid;

final class AssessmentTestId implements Stringable
{
    private const string NAMESPACE_UUID = '12345678-1234-5678-1234-567812345678';

    private readonly string $value;

    private ?int $questionnaireId = null;

    private function __construct(string $value)
    {
        if (!self::isValid($value)) {
            throw new InvalidArgumentException(sprintf('The provided value `%s` is invalid', $value));
        }

        $this->value = $value;
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match("/^[{]?[0-9a-fA-F]{8}-([0-9a-fA-F]{4}-){3}[0-9a-fA-F]{12}[}]?$/", $value);
    }

    public static function fromQuestionnaireId(int $questionnaireId): self
    {
        $uuid = Uuid::v5(Uuid::fromString(self::NAMESPACE_UUID), (string) $questionnaireId);

        $self = new self($uuid->toString());
        $self->questionnaireId = $questionnaireId;

        return $self;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }

    public function questionnaireId(): ?int
    {
        return $this->questionnaireId;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
