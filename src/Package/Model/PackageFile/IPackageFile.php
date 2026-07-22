<?php

declare(strict_types=1);

namespace Qti3\Package\Model\PackageFile;

use Qti3\Package\Model\FileContent\IFileContent;

interface IPackageFile
{
    public function getFilepath(): string;

    public function getContent(): IFileContent;

    public function isBinary(): bool;

    /**
     * Whether this file's content was generated or replaced in memory rather
     * than being a verbatim passthrough of the source it was loaded from. A
     * writer may use this to leave unchanged files in place when it writes back
     * over the package's original location.
     */
    public function isModified(): bool;
}
