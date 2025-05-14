<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration;

use App\SharedKernel\Domain\AbstractCollection;
use InvalidArgumentException;

/**
 * @template-extends AbstractCollection<OutcomeDeclaration>
 */
class OutcomeDeclarationCollection extends AbstractCollection
{
    public function getType(): string
    {
        return OutcomeDeclaration::class;
    }

    public function getByIdentifier(string $identifier): OutcomeDeclaration
    {
        $result = $this->filter(fn(OutcomeDeclaration $outcomeDeclaration): bool => $outcomeDeclaration->identifier === $identifier);

        $first = $result->first();

        if (!$first) {
            throw new InvalidArgumentException("Outcome declaration with identifier $identifier not found");
        }

        return $first;
    }

}
