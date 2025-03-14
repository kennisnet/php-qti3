<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItem;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\XmlFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;

readonly class ItemResourceBuilder
{
    public function __construct(
        private IXmlBuilder $xmlBuilder
    ) {}

    public function build(
        string $itemRefIdentifier,
        AssessmentItem $assessmentItem,
        ManifestResourceDependencyCollection $resourceDependencies
    ): Resource {
        return new Resource(
            $itemRefIdentifier,
            ResourceType::ASSESSMENT_ITEM,
            $itemRefIdentifier . '.xml',
            new ResourceFileCollection(
                [new ResourceFile($itemRefIdentifier . '.xml', new XmlFileContent($this->xmlBuilder->generateXmlFromObject($assessmentItem)))],
            ),
            $resourceDependencies,
        );
    }
}
