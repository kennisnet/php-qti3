<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IProcessingElement;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;
use App\SharedKernel\Domain\Qti\State\ItemState;
use App\SharedKernel\Domain\StringCollection;

class ResponseElse extends QtiElement
{
    public function __construct(
        /** @var array<int, IProcessingElement> */
        public readonly array $processingElements
    ) {}

    public function children(): array
    {
        return [
            ...$this->processingElements,
        ];
    }

    public function processResponses(ItemState $state): void
    {
        foreach ($this->processingElements as $processingElement) {
            $processingElement->processResponses($state);
        }
    }

    public function validate(StringCollection $identifiers): StringCollection
    {
        $errors = new StringCollection();

        foreach ($this->processingElements as $processingElement) {
            $errors = $errors->mergeWith($processingElement->validate($identifiers));
        }

        return $errors;
    }
}
