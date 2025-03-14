<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Manifest;

use App\SharedKernel\Domain\Qti\Shared\Xml\Reader\IXmlReader;

class ManifestFactory implements IManifestFactory
{
    public function __construct(
        private readonly IXmlReader $xmlReader,
    ) {}

    public function createFromXmlString(string $xmlContent): Manifest
    {
        return Manifest::fromString($xmlContent, $this->xmlReader);
    }
}
