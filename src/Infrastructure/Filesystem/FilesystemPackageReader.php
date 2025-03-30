<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Filesystem;

use App\SharedKernel\Domain\Exception\ResourceNotFoundException;
use App\SharedKernel\Domain\Qti\Package\Model\IPackageReader;
use DateTimeImmutable;
use League\Flysystem\FilesystemOperator;

readonly class FilesystemPackageReader implements IPackageReader
{
    public function __construct(
        private string $folderName,
        private FilesystemOperator $qtiPackageStorage,
    ) {
        if (!$this->qtiPackageStorage->directoryExists($this->folderName)) {
            throw new ResourceNotFoundException(sprintf('Folder %s not found', $this->folderName));
        }
    }

    public function readFile(string $filepath): string
    {
        return $this->qtiPackageStorage->read($this->folderName . '/' . $filepath);
    }

    public function getLastModified(): ?DateTimeImmutable
    {
        return new DateTimeImmutable()->setTimestamp($this->qtiPackageStorage->lastModified($this->folderName));
    }
}
