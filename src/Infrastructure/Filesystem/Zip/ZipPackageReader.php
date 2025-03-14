<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Filesystem\Zip;

use App\SharedKernel\Domain\Qti\Package\Model\IPackageReader;
use App\SharedKernel\Infrastructure\Filesystem\Zip\Factory\ZipArchiveFactory;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use ZipArchive;

readonly class ZipPackageReader implements IPackageReader
{
    private ZipArchive $zip;

    public function __construct(
        string $zipfilePath,
        ZipArchiveFactory $zipArchiveFactory,
    ) {
        $this->zip = $zipArchiveFactory->create();

        if ($this->zip->open($zipfilePath) !== true) {
            throw new BadRequestException('Could not open ZIP file');
        }
    }

    public function readFile(string $filepath): string
    {
        $content = $this->zip->getFromName($filepath);
        if ($content === false) {
            throw new BadRequestException(sprintf('File %s not found in ZIP', $filepath));
        }

        return $content;
    }

    public function getLastModified(): ?DateTimeImmutable
    {
        return null;
    }
}
