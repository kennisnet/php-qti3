<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\GapMatchInteraction;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

final class Gap extends QtiElement
{
    public function __construct(
        readonly public string $identifier,
    ) {}

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
        ];
    }
}
