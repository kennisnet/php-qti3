<?php

declare(strict_types=1);

namespace App\Toetsen\Domain\StoredQtiPackage\Validator;

use App\SharedKernel\Domain\StringCollection;
use App\Toetsen\Domain\StoredQtiPackage\StoredQtiPackage;

class MetadataValidationRuleSet implements IValidationRuleSet
{
    public const int MINIMAL_KEYWORD_COUNT = 1;

    public function validate(StoredQtiPackage $qtiPackage): StringCollection
    {
        $errors = new StringCollection();

        $metadata = $qtiPackage->qtiPackage->getMetadata();
        if ($metadata === null) {
            $errors->add('Metadata is required');

            return $errors;
        }

        $lom = $metadata->getLearningObjectMetadata();

        if (empty($lom->getGeneralContainer()->getTitle())) {
            $errors->add('Title is required');
        }
        if (empty($lom->getGeneralContainer()->getDescription())) {
            $errors->add('Description is required');
        }
        if ($lom->getGeneralContainer()->getKeywords()->count() < self::MINIMAL_KEYWORD_COUNT) {
            $errors->add(sprintf(
                'At least %d keyword%s required',
                self::MINIMAL_KEYWORD_COUNT,
                // @phpstan-ignore-next-line
                self::MINIMAL_KEYWORD_COUNT > 1 ? 's are' : ' is'
            ));
        }
        if ($lom->getClassificationContainer()->getDisciplineValues()->count() === 0) {
            $errors->add('Discipline is required');
        }
        if ($lom->getClassificationContainer()->getEducationalLevelIds()->count() === 0) {
            $errors->add('Educational level is required');
        }
        if ($lom->getGeneralContainer()->getIdentifiers()->count() === 0) {
            $errors->add('Identifier is required');
        }

        return $errors;
    }
}
