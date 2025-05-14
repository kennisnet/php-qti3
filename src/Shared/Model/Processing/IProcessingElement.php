<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\IXmlElement;
use App\SharedKernel\Domain\Qti\State\ItemState;

interface IProcessingElement extends IXmlElement
{
    public function processResponses(ItemState $state): void;
}
