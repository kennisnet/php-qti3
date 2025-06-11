<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\StringCollection;
use App\Toetsen\Domain\StoredQtiPackage\StoredQtiPackage;
use InvalidArgumentException;
use Throwable;

readonly class QtiPackageValidator
{
    /**
     * @param iterable<IValidationRuleSet> $validationRuleSets
     */
    public function __construct(
        private IImsQtiPackageValidator $imsQtiPackageValidator,
        private iterable $validationRuleSets
    ) {}

    public function validate(string $qtiPackageFilename): StringCollection
    {
        $errors = $this->imsQtiPackageValidator->validateQtiPackage($qtiPackageFilename);

        if (count($errors) > 0) {
            return $errors;
        }

        $errors = new StringCollection();
        foreach ($this->validationRuleSets as $validationRuleSet) {
            $errors = $errors->mergeWith($validationRuleSet->validate($qtiPackage));
        }

        if (count($errors) > 0) {
            throw new $exceptionClass($errors);
        }
    }
}
