<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\StringCollection;

readonly class QtiPackageValidator
{
    public function validate(QtiPackage $qtiPackage): StringCollection
    {
        $validators = [
            new ResponseProcessingValidator()
        ];

        $errors = new StringCollection();
        foreach ($validators as $validator) {
            $errors = $errors->mergeWith($validator->validate($qtiPackage));
        }

        return $errors;
    }
}
