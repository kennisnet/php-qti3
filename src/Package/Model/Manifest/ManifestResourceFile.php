<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Manifest;

class ManifestResourceFile
{
    public function __construct(
        public string $href,
    ) {}
}
