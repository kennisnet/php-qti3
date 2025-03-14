<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\ResourceFile;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\IFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFile;

class ResourceFile extends PackageFile
{
    public function __construct(
        public readonly string $href,
        IFileContent $content,
        private readonly bool $isBinary = false,
    ) {
        parent::__construct($href, $content);
    }

    public function isBinary(): bool
    {
        return $this->isBinary;
    }
}
