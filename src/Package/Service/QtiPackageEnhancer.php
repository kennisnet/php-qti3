<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service;

use App\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTest;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTestId;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef\AssessmentItemRefCollection;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section\AssessmentSection;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section\AssessmentSectionCollection;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\NavigationMode;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\SubmissionMode;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\TestPart;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\TestPartCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceType;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\TestResourceBuilder;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;

class QtiPackageEnhancer
{
    public function __construct(
        private readonly TestResourceBuilder $testResourceBuilder
    ) {}

    public function enhancePackage(QtiPackage $package): void
    {
        $testResourceMissing = count($package->resources->filterByType(ResourceType::ASSESSMENT_TEST)) === 0;

        if ($testResourceMissing) {
            $this->addTestResource($package);
        }
    }

    private function addTestResource(QtiPackage $package): void
    {
        $package->addResource($this->testResourceBuilder->build(
            $this->generateTest($package),
            new ManifestResourceDependencyCollection()
        ));
    }

    private function getAssessmentItemRefs(QtiPackage $package): AssessmentItemRefCollection
    {
        $itemFiles = $package->resources->filterByType(ResourceType::ASSESSMENT_ITEM);

        return new AssessmentItemRefCollection(
            array_map(
                fn(Resource $resource): AssessmentItemRef => new AssessmentItemRef(
                    $resource->identifier,
                    $resource->href ?? '',
                ),
                $itemFiles->all()
            )
        );
    }

    private function generateTest(QtiPackage $package): AssessmentTest
    {
        return new AssessmentTest(
            AssessmentTestId::generate(),
            new OutcomeDeclarationCollection(),
            new TestPartCollection([
                new TestPart(
                    'testPart',
                    NavigationMode::LINEAR,
                    SubmissionMode::INDIVIDUAL,
                    new AssessmentSectionCollection([
                        new AssessmentSection(
                            'section',
                            'Section',
                            $this->getAssessmentItemRefs($package),
                        ),
                    ])
                ),
            ])
        );
    }
}
