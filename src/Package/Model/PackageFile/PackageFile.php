<?php

declare(strict_types=1);

namespace Qti3\Package\Model\PackageFile;

use Qti3\Package\Model\FileContent\IFileContent;

class PackageFile implements IPackageFile
{
    private bool $modified;

    /**
     * @param bool $modified Whether this file's content was generated or
     *   replaced in memory. Defaults to true: a file constructed here is new
     *   content and must be written. Only a verbatim passthrough of a source
     *   file (produced by the package reader) is constructed with false.
     */
    public function __construct(
        private readonly string $filepath,
        protected readonly IFileContent $content,
        private readonly bool $isBinary = false,
        bool $modified = true,
    ) {
        $this->modified = $modified;
    }

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
        return $this->isBinary;
    }

    public function isModified(): bool
    {
        return $this->modified;
    }

    public function markModified(): void
    {
        $this->modified = true;
    }
}
