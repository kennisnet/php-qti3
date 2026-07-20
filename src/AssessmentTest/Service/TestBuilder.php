<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service;

use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\AssessmentTest\Service\Parser\AssessmentTestParser;
use Qti3\Package\Model\PackageFile\XmlFile;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\ResourceType;
use RuntimeException;

final readonly class TestBuilder
{
    public function __construct(
        private AssessmentTestParser $assessmentTestParser,
        private AssessmentTestSupportValidator $supportValidator,
    ) {}

    /**
     * Build the typed {@see AssessmentTest} model for a test resource in the
     * package. The test is first checked against the subset the model can
     * represent: a construct the model would drop on regeneration (outcome
     * processing, test feedback, rubric blocks, nested sections, ...) is
     * refused with {@see \Qti3\Shared\Exception\UnsupportedQtiConstructException}
     * rather than silently lost.
     */
    public function buildFromPackage(QtiPackage $package, ?string $testIdentifier = null): AssessmentTest
    {
        $testIdentifier ??= $package->getAssessmentTestIdentifier();

        $resource = $package->getResource($testIdentifier, ResourceType::ASSESSMENT_TEST);

        $xmlFile = $resource->getMainFile();
        if (!$xmlFile instanceof XmlFile) {
            throw new RuntimeException(sprintf('Main file of resource %s is not an XML file', $testIdentifier));
        }

        $this->supportValidator->assertSupported($xmlFile->getDocumentElement());

        return $this->assessmentTestParser->parse($xmlFile->getDocumentElement());
    }
}
