<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service;

use App\SharedKernel\Domain\Qti\Package\Model\IPackageReader;
use App\SharedKernel\Domain\Qti\Package\Model\IPackageWriter;

interface IPackageFactory
{
    public function getReader(string $filepath): IPackageReader;

    public function getWriter(string $filename): IPackageWriter;
}
