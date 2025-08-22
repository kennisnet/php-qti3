<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Qti\AssessmentItem\Service\ResponseProcessor;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\XmlFile;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceType;
use App\SharedKernel\Domain\StringCollection;
use Exception;

readonly class ResponseProcessingValidator implements IQtiPackageValidator
{
    public function __construct(
        private ResponseProcessor $responseProcessor,
    ) {}

    public function validate(QtiPackage $qtiPackage): StringCollection
    {
        $errors = new StringCollection();

        $itemResources = $qtiPackage->resources->filterByType(ResourceType::ASSESSMENT_ITEM);

        /** @var Resource $itemResource */
        foreach ($itemResources as $itemResource) {
            /** @var XmlFile $itemFile */
            $itemFile = $itemResource->getMainFile();

            try {
                $this->responseProcessor->initItemState((string) $itemFile);
            } catch (QtiPackageValidationError $error) {
                /** @var string $validationError */
                foreach ($error->validationErrors() as $validationError) {
                    $errors->add($itemFile->getFilepath() . ': ' . $validationError);
                }
            } catch (Exception $exception) {
                $errors->add($itemFile->getFilepath() . ': ' . $exception->getMessage());
            }
        }

        return $errors;
    }
}
