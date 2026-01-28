<?php

declare(strict_types=1);

namespace Qti3\Tests\Package\Model\Manifest;

use Qti3\Package\Model\Manifest\Manifest;
use Qti3\Infrastructure\Serializer\XmlReader;

class ManifestMock extends Manifest
{
    public static function create(): Manifest
    {
        $xmlReader = new XmlReader();
        return Manifest::fromString($xmlReader->read('<manifest />')->saveXML(), $xmlReader);
    }
}
