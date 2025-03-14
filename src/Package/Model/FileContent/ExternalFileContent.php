<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\FileContent;

class ExternalFileContent implements IFileContent
{
    public function __construct(
        public readonly string $url
    ) {}
}
