<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Manifest;

readonly class ManifestResourceDependency
{
    public function __construct(
        public string $identifierref
    ) {}
}
