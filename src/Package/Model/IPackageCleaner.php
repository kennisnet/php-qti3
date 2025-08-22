<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model;

interface IPackageCleaner
{
    public function cleanPackageStorage(QtiPackage $qtiPackage): void;
}
