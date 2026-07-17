<?php

declare(strict_types=1);

namespace Qti3\Package\Model;

use Qti3\Package\Model\Resource\Resource;

/**
 * Adds, updates and reorders assessment items in a single extracted QTI
 * package folder. Every operation goes through the {@see QtiPackage} domain
 * model and returns the affected {@see Resource}. Obtain one per folder from
 * {@see \Qti3\QtiClient::getItemEditor()}.
 */
interface IItemEditor
{
    public function addItem(string $itemXml): Resource;

    public function updateItem(string $identifier, string $itemXml): Resource;

    /** @param list<string> $orderedIdentifiers */
    public function reorderItems(array $orderedIdentifiers): void;
}
