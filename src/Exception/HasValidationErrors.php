<?php

declare(strict_types=1);

namespace Qti3\Exception;

use Qti3\StringCollection;

interface HasValidationErrors
{
    public function validationErrors(): StringCollection;
}
