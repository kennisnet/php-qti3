<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\AbstractQtiExpression;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IProcessingElement;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;

class ResponseElseIf extends QtiElement
{
    public function __construct(
        public readonly AbstractQtiExpression $condition,
        /** @var array<int, IProcessingElement> */
        public readonly array $processingElements
    ) {}

    public function children(): array
    {
        return [
            $this->condition,
            ...$this->processingElements,
        ];
    }

    public function processResponses(ItemState $state): void
    {
        foreach ($this->processingElements as $processingElement) {
            $processingElement->processResponses($state);
        }
    }

    public function validate(ItemState $itemState): StringCollection
    {
        $errors = $this->condition->validate($itemState);

        foreach ($this->processingElements as $processingElement) {
            $errors = $errors->mergeWith($processingElement->validate($itemState));
        }

        return $errors;
    }
}
