<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\StringCollection;

interface IValidator
{
    public function validate(QtiPackage $qtiPackage): StringCollection;
}
