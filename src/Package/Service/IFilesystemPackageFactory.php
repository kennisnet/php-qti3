<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service;

use App\SharedKernel\Domain\Qti\Package\Model\IPackageReader;
use App\SharedKernel\Domain\Qti\Package\Model\IPackageWriter;

interface IFilesystemPackageFactory
{
    public function getReader(string $folder, bool $lazyLoading = true): IPackageReader;

    public function getWriter(string $folder): IPackageWriter;
}
