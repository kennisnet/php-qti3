<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\StringCollection;

interface IImsQtiPackageValidator
{
    public function validateQtiPackage(string $qtiPackageFilename): StringCollection;
}
