<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder;

use App\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTest;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\XmlFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependency;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;

readonly class TestResourceBuilder
{
    public const ASSESSMENT_TEST_FILE_NAME = 'AssessmentTest.xml';

    public function __construct(
        private IXmlBuilder $xmlBuilder
    ) {}

    public function build(AssessmentTest $assessmentTest, string $identifier = 'TEST'): Resource
    {
        return new Resource(
            $identifier,
            ResourceType::ASSESSMENT_TEST,
            self::ASSESSMENT_TEST_FILE_NAME,
            new ResourceFileCollection([
                new ResourceFile(self::ASSESSMENT_TEST_FILE_NAME, new XmlFileContent($this->xmlBuilder->generateXmlFromObject($assessmentTest))),
            ]),
            new ManifestResourceDependencyCollection(array_map(
                fn(AssessmentItemRef $itemRef): ManifestResourceDependency => new ManifestResourceDependency($itemRef->identifier),
                $assessmentTest->getItemRefs()
            )),
        );
    }
}
