<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItemId;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

final class AssessmentItemRef extends QtiElement
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $href,
        public readonly ?AssessmentItemId $itemId = null,
        public readonly ?string $category = null,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
            'href' => $this->href,
            'category' => $this->category,
        ];
    }
}
