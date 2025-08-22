<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder;

use App\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTest;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependency;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\XmlFile;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceType;
use App\SharedKernel\Domain\Qti\Shared\Xml\Reader\IXmlReader;

readonly class TestResourceBuilder
{
    public const ASSESSMENT_TEST_FILE_NAME = 'AssessmentTest.xml';

    public function __construct(
        private IXmlBuilder $xmlBuilder,
        private IXmlReader $xmlReader,
    ) {}

    public function build(
        AssessmentTest $assessmentTest,
        ManifestResourceDependencyCollection $resourceDependencies,
        string $identifier = 'TEST',
    ): Resource {
        /** @var string $xml */
        $xml = $this->xmlBuilder->generateXmlFromObject($assessmentTest)->saveXML();

        return new Resource(
            $identifier,
            ResourceType::ASSESSMENT_TEST,
            self::ASSESSMENT_TEST_FILE_NAME,
            new PackageFileCollection([
                new XmlFile(
                    self::ASSESSMENT_TEST_FILE_NAME,
                    new MemoryFileContent($xml),
                    $this->xmlReader,
                ),
            ]),
            new ManifestResourceDependencyCollection([
                ...array_map(
                    fn(AssessmentItemRef $itemRef): ManifestResourceDependency => new ManifestResourceDependency($itemRef->identifier),
                    $assessmentTest->getItemRefs()
                ),
                ...$resourceDependencies->all(),
            ]),
        );
    }
}
