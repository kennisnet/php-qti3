<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model;

use App\SharedKernel\Domain\Qti\Package\Model\Manifest\Manifest;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackageId;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceCollection;
use App\SharedKernel\Infrastructure\Xml\XmlReader;

class QtiPackageMock extends QtiPackage
{
    public function __construct(
        ?QtiPackageId $id = null,
        ResourceCollection $resources = new ResourceCollection([]),
        ?Manifest $manifest = null,
    ) {
        parent::__construct(
            $id ?? QtiPackageId::generate(),
            $resources,
            $manifest ?? Manifest::fromString(
                '<manifest xmlns="http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="https://purl.imsglobal.org/spec/qti/v3p0/schema/xsd/imsqti_asiv3p0_v1p0.xsd https://purl.imsglobal.org/spec/md/v1p3/schema/xsd/imsmd_loose_v1p3p2.xsd http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1 https://purl.imsglobal.org/spec/qti/v3p0/schema/xsd/imsqtiv3p0_imscpv1p2_v1p0.xsd" identifier="MANIFEST_QTI"></manifest>',
                new XmlReader()
            ),
        );
    }
}
