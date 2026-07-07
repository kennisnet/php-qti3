<?php

declare(strict_types=1);

namespace Qti3\Package\Model\Item;

/**
 * Result of adding or updating an assessment item in a package: the identifier
 * the item is stored under and the XML as it was written to disk.
 *
 * TODO: media management followup ticket — once item XML resources (images,
 * etc.) are extracted and registered in the manifest, this will likely need a
 * `resources` property so callers can see which resources were added/reused
 * for the item.
 */
final readonly class EditedItem
{
    public function __construct(
        public string $identifier,
        public string $xml,
    ) {}
}
