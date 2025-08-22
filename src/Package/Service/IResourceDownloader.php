<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service;

interface IResourceDownloader
{
    public function downloadFileToFilesystem(
        string $sourceUrl,
        string $targetFilePath
    ): string;

    /**
     * @return iterable<string>
     */
    public function downloadFileToStream(
        string $sourceUrl
    ): iterable;
}
