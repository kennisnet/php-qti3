<?php

declare(strict_types=1);

namespace Qti3\Package\Model;

use Qti3\Package\Model\Item\EditedItem;

/**
 * Adds and updates assessment items inside a single extracted QTI package
 * folder. Obtained per folder from {@see \Qti3\Package\Service\IFilesystemPackageFactory::getItemEditor()},
 * mirroring the reader/writer factory methods.
 */
interface IItemEditor
{
    public function addItem(string $itemXml): EditedItem;

    public function updateItem(string $identifier, string $itemXml): EditedItem;

    /** @param list<string> $orderedIdentifiers */
    public function reorderItems(array $orderedIdentifiers): void;
}
