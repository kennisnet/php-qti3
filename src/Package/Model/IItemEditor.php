<?php

declare(strict_types=1);

namespace Qti3\Package\Model;

use Qti3\Package\Exception\CannotRemoveLastItemException;
use Qti3\Package\Model\Item\EditedItem;
use Qti3\Shared\Exception\ResourceNotFoundException;

/**
 * Adds, updates, reorders and removes assessment items inside a single
 * extracted QTI package folder. Obtained per folder from
 * {@see \Qti3\Package\Service\IFilesystemPackageFactory::getItemEditor()},
 * mirroring the reader/writer factory methods.
 */
interface IItemEditor
{
    public function addItem(string $itemXml): EditedItem;

    public function updateItem(string $identifier, string $itemXml): EditedItem;

    /** @param list<string> $orderedIdentifiers */
    public function reorderItems(array $orderedIdentifiers): void;

    /**
     * Remove an item: delete its resource entry (plus dependency references)
     * from the manifest, its item ref from the assessment test, the item file
     * itself, and any files no longer referenced by the remaining resources.
     *
     * @throws ResourceNotFoundException when the item is not in the manifest
     * @throws CannotRemoveLastItemException when the item is the last item left in the package
     */
    public function removeItem(string $identifier): void;
}
