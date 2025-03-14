<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model;

readonly class QtiResource
{
    public function __construct(
        public string $type,
        public string $originalPath,
        public string $relativePath,
        public string $filename,
        public bool $isBinary = true,
    ) {}
}
