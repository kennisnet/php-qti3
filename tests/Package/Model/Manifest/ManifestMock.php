<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\Manifest;

use App\SharedKernel\Domain\Qti\Package\Model\Manifest\Manifest;
use App\SharedKernel\Infrastructure\Serializer\XmlReader;

class ManifestMock extends Manifest
{
    public static function create(): Manifest
    {
        $xmlReader = new XmlReader();
        return Manifest::fromString($xmlReader->read('<manifest />')->saveXML(), $xmlReader);
    }
}
