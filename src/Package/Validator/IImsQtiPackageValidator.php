<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\StringCollection;

interface IImsQtiPackageValidator extends IQtiPackageValidator
{
    public function validateZipPackage(string $qtiPackageFilename): StringCollection;

    public function validate(QtiPackage $qtiPackage): StringCollection;
}
