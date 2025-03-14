<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\PackageFile;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\IFileContent;

interface IPackageFile
{
    public function getFilepath(): string;

    public function getContent(): IFileContent;

    public function isBinary(): bool;
}
