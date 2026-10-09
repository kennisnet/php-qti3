<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\ResponseDeclaration;

use Qti3\Shared\Model\QtiElement;

class AreaMapping extends QtiElement
{
    /**
     * @param array<int,AreaMapEntry> $entries
     */
    public function __construct(
        public readonly array $entries,
        public readonly ?float $defaultValue = null,
        public readonly ?float $lowerBound = null,
        public readonly ?float $upperBound = null,
    ) {}

    public function attributes(): array
    {
        $attributes = [];
        if ($this->defaultValue !== null) {
            $attributes['default-value'] = (string) $this->defaultValue;
        }
        if ($this->lowerBound !== null) {
            $attributes['lower-bound'] = (string) $this->lowerBound;
        }
        if ($this->upperBound !== null) {
            $attributes['upper-bound'] = (string) $this->upperBound;
        }

        return $attributes;
    }

    public function children(): array
    {
        return $this->entries;
    }
}
