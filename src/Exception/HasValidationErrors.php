<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Exception;

use App\SharedKernel\Domain\StringCollection;

interface HasValidationErrors
{
    public function validationErrors(): StringCollection;
}
