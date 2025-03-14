<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Filesystem;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\ExternalFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\IMemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\LocalFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\IPackageWriter;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;

readonly class FilesystemPackageWriter implements IPackageWriter
{
    public function __construct(
        private string $folderName,
        private FilesystemOperator $qtiPackageStorage,
        private FileSystemUtils $fileSystemUtils,
        private ResourceDownloader $resourceDownloader
    ) {}

    public function write(QtiPackage $qtiPackage): void
    {
        $this->qtiPackageStorage->createDirectory($this->folderName);

        foreach ($qtiPackage->getFiles() as $resourceFile) {
            $filePath = $this->folderName . '/' . $resourceFile->getFilepath();
            $content = $resourceFile->getContent();
            if ($content instanceof IMemoryFileContent) {
                $this->qtiPackageStorage->write($filePath, (string) $content);
            } elseif ($content instanceof LocalFileContent) {
                $fileContents = $this->fileSystemUtils->getFileContents($content->filepath);
                $this->qtiPackageStorage->write($filePath, $fileContents);
            } elseif ($content instanceof ExternalFileContent) {
                $this->resourceDownloader->downloadFile($content->url, $filePath);
            } else {
                throw new InvalidArgumentException(sprintf('Unsupported content type: %s', $content::class));
            }
        }
    }

    public function getPublicUrl(): string
    {
        return $this->qtiPackageStorage->publicUrl($this->folderName);
    }
}
