<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model;

use InvalidArgumentException;
use Stringable;
use Symfony\Component\Uid\Uuid;

final class AssessmentItemId implements Stringable
{
    private const string NAMESPACE_UUID = '12345678-1234-5678-1234-567812345678';

    private readonly string $value;

    private ?int $questionnaireId = null;

    private ?int $questionnaireItemIndex = null;

    private function __construct(string $value)
    {
        if (!self::isValid($value)) {
            throw new InvalidArgumentException(sprintf('The provided value `%s` is invalid', $value));
        }

        $this->value = $value;
    }

    public static function fromQuestionnaire(int $questionnaireId, int $questionnaireItemIndex): self
    {
        $uuid = Uuid::v5(Uuid::fromString(self::NAMESPACE_UUID), $questionnaireId . ':' . $questionnaireItemIndex);

        $self = new self($uuid->toString());
        $self->questionnaireId = $questionnaireId;
        $self->questionnaireItemIndex = $questionnaireItemIndex;

        return $self;
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match("/^[{]?[0-9a-fA-F]{8}-([0-9a-fA-F]{4}-){3}[0-9a-fA-F]{12}[}]?$/", $value);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function questionnaireId(): ?int
    {
        return $this->questionnaireId;
    }

    public function questionnaireItemIndex(): ?int
    {
        return $this->questionnaireItemIndex;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(AssessmentItemId $assessmentItemId): bool
    {
        return $this->value === $assessmentItemId->value;
    }
}
