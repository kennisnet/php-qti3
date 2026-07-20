<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Model;

use Qti3\Package\Model\Resource\Resource;

/**
 * Adds, updates and reorders assessment items in a single extracted QTI
 * package folder. Every operation goes through the typed {@see AssessmentTest}
 * and {@see \Qti3\AssessmentItem\Model\AssessmentItem} models and returns the
 * affected {@see Resource} of the regenerated package. Obtain one per folder
 * from {@see \Qti3\QtiClient::getItemEditor()}.
 */
interface IItemEditor
{
    public function addItem(string $itemXml): Resource;

    public function updateItem(string $identifier, string $itemXml): Resource;

    /** @param list<string> $orderedIdentifiers */
    public function reorderItems(array $orderedIdentifiers): void;
}
