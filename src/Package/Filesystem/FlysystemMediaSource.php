<?php

declare(strict_types=1);

namespace Qti3\Package\Filesystem;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Qti3\Package\Model\FileContent\FlysystemFileContent;
use Qti3\Package\Model\FileContent\IFileContent;
use Qti3\Package\Model\IMediaSource;

/**
 * Media source backed by a {@see FilesystemOperator} rooted at the package
 * source's root directory. The sandboxing comes from Flysystem itself: paths
 * are resolved inside the operator's root, and a traversal attempt (`../…`)
 * is answered with "no such file" instead of an exception, so hostile
 * references in item content degrade to the regular refusal warning.
 */
final readonly class FlysystemMediaSource implements IMediaSource
{
    public function __construct(
        private FilesystemOperator $filesystem,
    ) {}

    public function hasFile(string $filepath): bool
    {
        try {
            return $this->filesystem->fileExists($filepath);
        } catch (FilesystemException) {
            return false;
        }
    }

    public function getFileContent(string $filepath): IFileContent
    {
        return new FlysystemFileContent($this->filesystem, $filepath);
    }
}
