<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service;

use App\SharedKernel\Domain\Qti\Package\Model\IPackageReader;
use App\SharedKernel\Domain\Qti\Package\Model\IPackageWriter;

interface IZipPackageFactory
{
    public function getReader(string $zipfilePath): IPackageReader;

    public function getWriter(string $zipfilePath): IPackageWriter;
}
