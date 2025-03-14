<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Resource;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\ExternalFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\LocalFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;

class Webcontent extends Resource
{
    public function __construct(
        public readonly string $originalPath,
        string $identifier,
        string $filepath,
        bool $isBinary = true
    ) {
        $isExternal = str_contains($originalPath, '://');

        parent::__construct(
            $identifier,
            ResourceType::WEBCONTENT,
            $filepath,
            new ResourceFileCollection(
                [new ResourceFile($filepath, $isExternal ? new ExternalFileContent($originalPath) : new LocalFileContent($originalPath), $isBinary)]
            ),
            new ManifestResourceDependencyCollection(),
        );
    }
}
