<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Manifest;

interface IManifestFactory
{
    public function createFromXmlString(string $xmlContent): Manifest;
}
