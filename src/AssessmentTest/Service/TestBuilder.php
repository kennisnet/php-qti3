<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service;

use Qti3\AssessmentTest\Service\Parser\AssessmentTestParser;
use Qti3\Package\Model\PackageFile\XmlFile;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\ResourceType;
use RuntimeException;

final readonly class TestBuilder
{
    public function __construct(
        private AssessmentTestParser $assessmentTestParser,
    ) {}

    /**
     * Build the {@see \Qti3\AssessmentTest\Model\AssessmentTest} model for a test
     * resource, together with the warnings for any construct the model cannot
     * hold (outcome processing, test feedback, rubric blocks, nested sections,
     * ...). The test is not refused: the construct is reported as a warning and
     * dropped when the model is serialized again.
     */
    public function buildFromPackage(QtiPackage $package, ?string $testIdentifier = null): TestParseResult
    {
        $testIdentifier ??= $package->getAssessmentTestIdentifier();

        $resource = $package->getResource($testIdentifier, ResourceType::ASSESSMENT_TEST);

        $xmlFile = $resource->getMainFile();
        if (!$xmlFile instanceof XmlFile) {
            throw new RuntimeException(sprintf('Main file of resource %s is not an XML file', $testIdentifier));
        }

        return $this->assessmentTestParser->parse($xmlFile->getDocumentElement());
    }
}
