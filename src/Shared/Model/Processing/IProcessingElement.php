<?php

declare(strict_types=1);

namespace Qti3\Shared\Model\Processing;

use Qti3\Shared\Model\IXmlElement;
use Qti3\State\ItemState;
use Qti3\StringCollection;

interface IProcessingElement extends IXmlElement
{
    public function processResponses(ItemState $state): void;

    public function validate(ItemState $itemState): StringCollection;
}
