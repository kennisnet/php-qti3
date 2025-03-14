<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Manifest;

interface IManifestResourceProvider
{
    public function getManifestResource(): ManifestResource;
}
