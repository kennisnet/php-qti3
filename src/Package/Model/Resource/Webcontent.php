<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Resource;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\ExternalFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\LocalFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFile;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFileCollection;
use App\SharedKernel\Domain\Qti\Package\Service\IResourceDownloader;

class Webcontent extends Resource
{
    public function __construct(
        public readonly string $originalPath,
        string $identifier,
        string $filepath,
        private readonly IResourceDownloader $resourceDownloader,
        bool $isBinary = true,
    ) {
        $isExternal = str_contains($originalPath, '://');

        parent::__construct(
            $identifier,
            ResourceType::WEBCONTENT,
            $filepath,
            new PackageFileCollection(
                [new PackageFile($filepath, $isExternal ? new ExternalFileContent($originalPath, $this->resourceDownloader) : new LocalFileContent($originalPath), $isBinary)]
            ),
            new ManifestResourceDependencyCollection(),
        );
    }
}
