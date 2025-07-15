<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\StringCollection;

class QtiPackageValidator
{
    public function __construct(
        private readonly IImsQtiPackageValidator $imsValidator,
        private readonly ResponseProcessingValidator $responseProcessingValidator,
    ) {}

    public function validate(QtiPackage $qtiPackage): StringCollection
    {
        /** @var array<int, IQtiPackageValidator> $validators */
        $validators = [
            $this->imsValidator,
            $this->responseProcessingValidator,
        ];

        $errors = new StringCollection();

        foreach ($validators as $validator) {
            $errors = $errors->mergeWith($validator->validate($qtiPackage));
        }

        return $errors;
    }
}
