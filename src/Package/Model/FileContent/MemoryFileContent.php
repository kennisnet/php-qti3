<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\FileContent;

readonly class MemoryFileContent implements IMemoryFileContent
{
    public function __construct(
        public string $content
    ) {}

    public function __toString(): string
    {
        return $this->content;
    }
}
