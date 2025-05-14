<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\State;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclarationCollection;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\ValueConverter;
use App\SharedKernel\Domain\Qti\Shared\Model\Value;
use InvalidArgumentException;

class ResponseSet
{
    /**
     * @var array<string,string|int|float|bool|array<int,string|int|float|bool>|null> $responses
     */
    public array $responses = [];

    public function __construct(
        public ResponseDeclarationCollection $responseDeclarations,
    ) {}

    /**
     * @param array<string,string|int|float|bool|array<int,string|int|float|bool>|null> $responses
     */
    public function setResponses(array $responses): void
    {
        foreach ($responses as $identifier => $response) {
            $this->responses[$identifier] = $response;
        }
    }

    /**
     * @return string|int|float|bool|array<bool|float|int|string|null>|null
     */
    public function getCorrectResponse(string $identifier): string|int|float|bool|array|null
    {
        $responseDeclaration = $this->responseDeclarations->getByIdentifier($identifier);
        $correctResponse = $responseDeclaration->correctResponse;
        if ($correctResponse === null) {
            throw new InvalidArgumentException("Correct response for response declaration with identifier $identifier not found");
        }

        $result = ValueConverter::convert(
            array_map(fn(Value $value): string|int|float|bool => $value->value, $correctResponse->values),
            $responseDeclaration->cardinality,
            $responseDeclaration->baseType
        );

        return $result;
    }

    /**
     * @return null|string|int|float|bool|array<int,string|int|float|bool>
     */
    public function getResponseValue(string $identifier): string|int|float|bool|array|null
    {
        $responseDeclaration = $this->responseDeclarations->getByIdentifier($identifier);

        return $this->responses[$identifier] ?? null;
    }

    public function mapResponse(string $identifier): float
    {
        $responseDeclaration = $this->responseDeclarations->getByIdentifier($identifier);

        $mapping = $responseDeclaration->mapping;
        if ($mapping === null) {
            throw new InvalidArgumentException("Mapping for response declaration with identifier $identifier not found"); // @codeCoverageIgnore
        }

        $response = $this->getResponseValue($identifier);
        if (!is_array($response)) {
            throw new InvalidArgumentException("Response for response declaration with identifier $identifier is not an array"); // @codeCoverageIgnore
        }

        $result = 0;
        foreach ($mapping->entries as $entry) {
            $result += $entry->evaluate($response);
        }

        return $result;
    }
}
