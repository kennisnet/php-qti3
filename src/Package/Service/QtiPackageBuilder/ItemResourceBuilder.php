<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItem;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\XmlFile;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceType;
use App\SharedKernel\Domain\Qti\Shared\Xml\Reader\IXmlReader;

readonly class ItemResourceBuilder
{
    public function __construct(
        private IXmlBuilder $xmlBuilder,
        private IXmlReader $xmlReader,
    ) {}

    public function build(
        string $itemRefIdentifier,
        AssessmentItem $assessmentItem,
        ManifestResourceDependencyCollection $resourceDependencies
    ): Resource {
        /** @var string $xml */
        $xml = $this->xmlBuilder->generateXmlFromObject($assessmentItem)->saveXML();

        return new Resource(
            $itemRefIdentifier,
            ResourceType::ASSESSMENT_ITEM,
            $itemRefIdentifier . '.xml',
            new PackageFileCollection(
                [new XmlFile(
                    $itemRefIdentifier . '.xml',
                    new MemoryFileContent($xml),
                    $this->xmlReader
                )],
            ),
            $resourceDependencies,
        );
    }
}
