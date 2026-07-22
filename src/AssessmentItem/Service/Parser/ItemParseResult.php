<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\Shared\Collection\StringCollection;

/**
 * The typed item plus the warnings gathered while parsing it. A non-empty
 * `warnings` collection means the source contained constructs the model cannot
 * hold, so they are lost when the item is serialized again.
 */
final readonly class ItemParseResult
{
    public function __construct(
        public AssessmentItem $item,
        public StringCollection $warnings,
    ) {}
}
