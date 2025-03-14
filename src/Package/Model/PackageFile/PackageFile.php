<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\PackageFile;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\IFileContent;

class PackageFile implements IPackageFile
{
    public function __construct(
        private readonly string $filepath,
        protected readonly IFileContent $content
    ) {}

    public function getFilepath(): string
    {
        return $this->filepath;
    }

    public function getContent(): IFileContent
    {
        return $this->content;
    }

    public function isBinary(): bool
    {
        return false;
    }
}
