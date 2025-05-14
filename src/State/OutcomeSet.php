<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\State;

use App\SharedKernel\Domain\Qti\AssessmentItem\Service\ValueConverter;
use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\Shared\Model\DefaultValue;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclaration;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\Value;

class OutcomeSet
{
    /** @var array<string,string|int|float|bool|array<int|string,string|int|float|bool|null>|null> $outcomes */
    public array $outcomes = [];

    public function __construct(
        public OutcomeDeclarationCollection $outcomeDeclarations,
    ) {
        $this->outcomeDeclarations->add(
            new OutcomeDeclaration(
                'completionStatus',
                BaseType::IDENTIFIER,
                Cardinality::SINGLE,
                new DefaultValue(new Value('not_attempted')),
            )
        );
    }

    public function getOutcomeValue(string $identifier): mixed
    {
        $outcomeDeclaration = $this->outcomeDeclarations->getByIdentifier($identifier);

        if (array_key_exists($identifier, $this->outcomes)) {
            return ValueConverter::convert($this->outcomes[$identifier], $outcomeDeclaration->cardinality, $outcomeDeclaration->baseType);
        }

        if ($outcomeDeclaration->defaultValue) {
            return ValueConverter::convert($outcomeDeclaration->defaultValue->value->value, $outcomeDeclaration->cardinality, $outcomeDeclaration->baseType);
        }

        return null;
    }

    /**
     * @param string|int|float|bool|array<int,string|int|float|bool>|null $value
     */
    public function set(string $identifier, mixed $value): void
    {
        $outcomeDeclaration = $this->outcomeDeclarations->getByIdentifier($identifier);

        // Convert the value to ensure it matches the expected type
        $convertedValue = ValueConverter::convert($value, $outcomeDeclaration->cardinality, $outcomeDeclaration->baseType);

        $this->outcomes[$identifier] = $convertedValue;
    }
}
