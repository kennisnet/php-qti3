<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Filesystem\Zip;

use App\SharedKernel\Domain\Qti\Package\Model\IPackageReader;
use App\SharedKernel\Domain\Qti\Package\Model\IPackageWriter;
use App\SharedKernel\Domain\Qti\Package\Service\IPackageFactory;
use App\SharedKernel\Infrastructure\Filesystem\ResourceDownloader;
use App\SharedKernel\Infrastructure\Filesystem\Zip\Factory\ZipArchiveFactory;

class ZipPackageFactory implements IPackageFactory
{
    public function __construct(
        private readonly string $dataFolder,
        private readonly ResourceDownloader $resourceDownloader,
        private readonly ZipArchiveFactory $zipArchiveFactory,
    ) {}

    public function getReader(string $filepath): IPackageReader
    {
        return new ZipPackageReader($filepath, $this->zipArchiveFactory);
    }

    public function getWriter(string $filename): IPackageWriter
    {
        return new ZipPackageWriter($this->dataFolder . '/' . $filename, $this->resourceDownloader, $this->zipArchiveFactory);
    }
}
