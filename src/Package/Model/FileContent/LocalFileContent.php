<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\FileContent;

class LocalFileContent implements IFileContent
{
    public function __construct(
        public readonly string $filepath
    ) {}
}
