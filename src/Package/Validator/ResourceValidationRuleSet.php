<?php

declare(strict_types=1);

namespace App\Toetsen\Domain\StoredQtiPackage\Validator;

use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
use App\SharedKernel\Domain\StringCollection;
use App\Toetsen\Domain\StoredQtiPackage\StoredQtiPackage;

class ResourceValidationRuleSet implements IValidationRuleSet
{
    public const int MINIMAL_ITEM_COUNT = 5;

    public function validate(StoredQtiPackage $qtiPackage): StringCollection
    {
        $errors = new StringCollection();

        $itemCount = $qtiPackage->qtiPackage->resources->filterByType(ResourceType::ASSESSMENT_ITEM)->count();
        $assessmentTestCount = $qtiPackage->qtiPackage->resources->filterByType(ResourceType::ASSESSMENT_TEST)->count();

        if ($itemCount < self::MINIMAL_ITEM_COUNT) {
            $errors->add('Minimum item count is 5');
        }
        if ($assessmentTestCount === 0) {
            $errors->add('Package should contain an assessment test');
        }
        if ($assessmentTestCount > 1) {
            $errors->add('Package should contain only one assessment test');
        }

        return $errors;
    }
}
