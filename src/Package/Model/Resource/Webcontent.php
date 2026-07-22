<?php

declare(strict_types=1);

namespace Qti3\Package\Model\Resource;

use Qti3\Package\Model\FileContent\IFileContent;
use Qti3\Package\Model\Manifest\ManifestResourceDependencyCollection;
use Qti3\Package\Model\PackageFile\PackageFile;
use Qti3\Package\Model\PackageFile\PackageFileCollection;

class Webcontent extends Resource
{
    public function __construct(
        public readonly string $originalPath,
        string $identifier,
        string $filepath,
        IFileContent $content,
        bool $isBinary = true,
    ) {
        parent::__construct(
            $identifier,
            ResourceType::WEBCONTENT,
            $filepath,
            new PackageFileCollection(
                [new PackageFile(
                    $filepath,
                    $content,
                    $isBinary,
                    // Webcontent resources are only constructed for media that is
                    // being added to the package, so their file is always new.
                    modified: true,
                )],
            ),
            new ManifestResourceDependencyCollection(),
        );
    }
}
