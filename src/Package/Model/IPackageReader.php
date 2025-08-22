<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\IFileContent;
use DateTimeImmutable;

interface IPackageReader
{
    public function getFileContent(string $filepath): IFileContent;

    public function getLastModified(): ?DateTimeImmutable;

}
