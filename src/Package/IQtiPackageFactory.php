<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package;

use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;

interface IQtiPackageFactory
{
    public function fromFilesystem(string $filePath): QtiPackage;

    public function fromZip(string $filePath): QtiPackage;

}
