<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Qti\AssessmentItem\Service\ResponseProcessor;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\XmlFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
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
            /** @var ResourceFile $itemFile */
            $itemFile = $itemResource->getMainFile();

            /** @var XmlFileContent $xmlFileContent */
            $xmlFileContent = $itemFile->getContent();

            try {
                $this->responseProcessor->initItemState((string) $xmlFileContent);
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
