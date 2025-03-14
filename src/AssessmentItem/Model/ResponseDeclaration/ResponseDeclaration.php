<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class ResponseDeclaration extends QtiElement
{
    public function __construct(
        public readonly BaseType $baseType,
        public readonly Cardinality $cardinality = Cardinality::SINGLE,
        public readonly string $identifier = 'RESPONSE',
        public readonly ?CorrectResponse $correctResponse = null,
        public readonly ?Mapping $mapping = null,
        public readonly ?AreaMapping $areaMapping = null,
    ) {}

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
            'cardinality' => $this->cardinality->value,
            'base-type' => $this->baseType->value,
        ];
    }

    public function children(): array
    {
        return [
            $this->correctResponse,
            $this->mapping,
            $this->areaMapping,
        ];
    }
}
