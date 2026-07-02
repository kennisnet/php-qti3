<?php

declare(strict_types=1);

namespace Qti3\Package\Model\Item;

/**
 * Result of adding or updating an assessment item in a package: the identifier
 * the item is stored under and the XML as it was written to disk.
 */
final readonly class EditedItem
{
    public function __construct(
        public string $identifier,
        public string $xml,
    ) {}
}
