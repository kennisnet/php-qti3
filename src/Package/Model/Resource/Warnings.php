<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Resource;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFile;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFileCollection;
use App\SharedKernel\Domain\StringCollection;

class Warnings extends Resource
{
    public const string WARNINGS_FILE_NAME = 'warnings.txt';

    public function __construct(StringCollection $warnings)
    {
        parent::__construct(
            'WARNINGS',
            ResourceType::CONTROLFILE,
            self::WARNINGS_FILE_NAME,
            new PackageFileCollection([
                new PackageFile(self::WARNINGS_FILE_NAME, new MemoryFileContent($warnings->join(PHP_EOL))),
            ]),
            new ManifestResourceDependencyCollection(),
        );
    }
}
