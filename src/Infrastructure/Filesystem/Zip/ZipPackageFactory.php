<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Filesystem\Zip;

use App\SharedKernel\Domain\Qti\Package\Model\IPackageReader;
use App\SharedKernel\Domain\Qti\Package\Model\IPackageWriter;
use App\SharedKernel\Domain\Qti\Package\Service\IZipPackageFactory;
use App\SharedKernel\Infrastructure\Filesystem\ResourceDownloader;
use App\SharedKernel\Infrastructure\Filesystem\Zip\Factory\ZipArchiveFactory;

class ZipPackageFactory implements IZipPackageFactory
{
    public function __construct(
        private readonly string $dataFolder,
        private readonly ResourceDownloader $resourceDownloader,
        private readonly ZipArchiveFactory $zipArchiveFactory,
    ) {}

    public function getReader(string $zipfilePath): IPackageReader
    {
        return new ZipPackageReader($zipfilePath, $this->zipArchiveFactory);
    }

    public function getWriter(string $zipfilePath): IPackageWriter
    {
        return new ZipPackageWriter($this->dataFolder . '/' . $zipfilePath, $this->resourceDownloader, $this->zipArchiveFactory);
    }
}
