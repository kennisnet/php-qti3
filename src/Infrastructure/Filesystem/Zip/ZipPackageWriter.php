<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Filesystem\Zip;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\ExternalFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\IMemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\LocalFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\IPackageWriter;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\IPackageFile;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Infrastructure\Filesystem\ResourceDownloader;
use App\SharedKernel\Infrastructure\Filesystem\Zip\Exception\ZipArchiveOpenFileException;
use App\SharedKernel\Infrastructure\Filesystem\Zip\Factory\ZipArchiveFactory;
use InvalidArgumentException;
use ZipArchive;

readonly class ZipPackageWriter implements IPackageWriter
{
    public function __construct(
        private string $zipFilepath,
        private ResourceDownloader $resourceDownloader,
        private ZipArchiveFactory $zipArchiveFactory,
    ) {}

    public function write(QtiPackage $qtiPackage): void
    {
        $zipArchive = $this->getZipArchive($this->zipFilepath);

        foreach ($qtiPackage->getFiles() as $file) {
            $this->addFile($file, $zipArchive);
        }

        $zipArchive->close();
    }

    public function getPublicUrl(): string
    {
        return $this->zipFilepath;
    }

    private function getZipArchive(string $archiveFilePath): ZipArchive
    {
        $zipArchive = $this->zipArchiveFactory->create();

        $openResult = $zipArchive->open($archiveFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            throw new ZipArchiveOpenFileException($archiveFilePath);
        }

        return $zipArchive;
    }

    private function addFile(IPackageFile $file, ZipArchive $zipArchive): void
    {
        $content = $file->getContent();
        if ($content instanceof IMemoryFileContent) {
            $zipArchive->addFromString($file->getFilepath(), (string) $content);
        } elseif ($content instanceof LocalFileContent) {
            $zipArchive->addFile($content->filepath, $file->getFilepath());
        } elseif ($content instanceof ExternalFileContent) {
            $zipArchive->addFile(
                $this->resourceDownloader->downloadFileToFilesystem($content->url, md5($content->url)),
                $file->getFilepath()
            );
        } else {
            throw new InvalidArgumentException(sprintf('Unsupported content type: %s', $content::class));
        }
    }
}
