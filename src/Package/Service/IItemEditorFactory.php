<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

use Qti3\Package\Model\IItemEditor;

/**
 * Vends an {@see IItemEditor} bound to an extracted package folder.
 *
 * Kept separate from {@see IFilesystemPackageFactory} (Interface Segregation):
 * consumers that only read or write packages should not have to implement item
 * editing. A filesystem package factory may implement both.
 */
interface IItemEditorFactory
{
    public function getItemEditor(string $folder): IItemEditor;
}
