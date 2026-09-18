<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Model\OutcomeProcessing;

use Qti3\Shared\Model\BaseType;
use Qti3\Shared\Model\Cardinality;
use Qti3\Shared\Model\Processing\AbstractQtiExpression;
use Qti3\AssessmentItem\Model\State\ItemState;
use Qti3\Shared\Collection\StringCollection;

class TestVariables extends AbstractQtiExpression
{
    /**
     * The item-subset attributes (`section-identifier`, `include-category`,
     * `exclude-category`) and the value selectors (`weight-identifier`,
     * `base-type`) are carried as written so a parsed test regenerates unchanged.
     */
    public function __construct(
        public readonly string $variableIdentifier,
        public readonly ?string $includeCategory = null,
        public readonly ?string $sectionIdentifier = null,
        public readonly ?string $excludeCategory = null,
        public readonly ?string $weightIdentifier = null,
        public readonly ?string $baseType = null,
    ) {}

    public function attributes(): array
    {
        return [
            'variable-identifier' => $this->variableIdentifier,
            'include-category' => $this->includeCategory,
            'section-identifier' => $this->sectionIdentifier,
            'exclude-category' => $this->excludeCategory,
            'weight-identifier' => $this->weightIdentifier,
            'base-type' => $this->baseType,
        ];
    }

    // @codeCoverageIgnoreStart
    public function evaluate(ItemState $state): mixed
    {
        return null;
    }

    public function getBaseType(ItemState $state): BaseType
    {
        return BaseType::STRING;
    }

    public function getCardinality(ItemState $state): Cardinality
    {
        return Cardinality::SINGLE;
    }

    public function validate(ItemState $itemState): StringCollection
    {
        // TODO: Implement validate() method.

        return new StringCollection();
    }
    // @codeCoverageIgnoreEnd
}
