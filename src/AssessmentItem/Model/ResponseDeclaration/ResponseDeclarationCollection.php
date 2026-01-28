<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\ResponseDeclaration;

use Qti3\AbstractCollection;
use Qti3\StringCollection;
use InvalidArgumentException;

/**
 * @template-extends AbstractCollection<ResponseDeclaration>
 */
class ResponseDeclarationCollection extends AbstractCollection
{
    public function getType(): string
    {
        return ResponseDeclaration::class;
    }

    public function getByIdentifier(string $identifier): ResponseDeclaration
    {
        $result = $this->filter(fn(ResponseDeclaration $responseDeclaration): bool => $responseDeclaration->identifier === $identifier);

        $first = $result->first();

        if (!$first) {
            throw new InvalidArgumentException("Response declaration with identifier $identifier not found");
        }

        return $first;
    }

    public function getIdentifiers(): StringCollection
    {
        return new StringCollection(array_map(
            fn(ResponseDeclaration $responseDeclaration): string => $responseDeclaration->identifier,
            $this->all(),
        ));
    }
}
