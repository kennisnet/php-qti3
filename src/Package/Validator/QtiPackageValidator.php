<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\StringCollection;

readonly class QtiPackageValidator
{
    public function __construct(
        private IImsQtiPackageValidator $imsValidator,
        private ResponseProcessingValidator $responseProcessingValidator,
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
