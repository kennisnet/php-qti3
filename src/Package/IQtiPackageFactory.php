<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package;

use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackageId;

interface IQtiPackageFactory
{
    public function fromFilesystem(string $filePath, ?QtiPackageId $id = null): QtiPackage;

    public function fromZip(string $filePath, ?QtiPackageId $id = null): QtiPackage;

}
