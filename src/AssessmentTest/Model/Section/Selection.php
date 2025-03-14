<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class Selection extends QtiElement
{
    public function __construct(
        public readonly int $select,
        public readonly bool $withReplacement,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function attributes(): array
    {
        return [
            'select' => (string) $this->select,
            'with-replacement' => $this->withReplacement ? 'true' : 'false',
        ];
    }
}
