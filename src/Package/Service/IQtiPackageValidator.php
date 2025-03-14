<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service;

use App\SharedKernel\Domain\StringCollection;

interface IQtiPackageValidator
{
    public function validateQtiPackage(string $qtiPackageFilename): StringCollection;
}
