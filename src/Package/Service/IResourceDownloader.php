<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

interface IResourceDownloader
{
    public function downloadFileToFilesystem(
        string $sourceUrl,
        string $targetFilePath,
    ): string;

    /**
     * @return iterable<string>
     */
    public function downloadFileToStream(
        string $sourceUrl,
    ): iterable;
}
