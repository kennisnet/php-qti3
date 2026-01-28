<?php

declare(strict_types=1);

namespace Qti3\Infrastructure\Filesystem\Zip\Factory;

use ZipArchive;

class ZipArchiveFactory
{
    public function create(): ZipArchive
    {
        return new ZipArchive();
    }
}
