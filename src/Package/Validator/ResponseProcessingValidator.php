<?php

declare(strict_types=1);

namespace App\Toetsen\Domain\StoredQtiPackage\Validator;

use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ResponseProcessingParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\ResponseProcessor;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\XmlFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
use App\SharedKernel\Domain\Qti\Package\Validator\IValidator;
use App\SharedKernel\Domain\StringCollection;

class ResponseProcessingValidator implements IValidator
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
            $itemState = $this->responseProcessor->initItemState($xmlFileContent->xmlDocument->saveXML());
        }

        return $errors;
    }
}
