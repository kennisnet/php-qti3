<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Filesystem\Zip;

use App\SharedKernel\Application\Filesystem\Zip\IZipFileFinder;
use GlobIterator;

readonly class ZipFileFinder implements IZipFileFinder
{
    public function findIn(string $directory): iterable
    {
        return new GlobIterator($directory . '/*.zip');
    }
}
