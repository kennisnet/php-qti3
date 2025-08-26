<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Filesystem;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\IFileContent;
use League\Flysystem\FilesystemOperator;

class FlysystemFileContent implements IFileContent
{
    public function __construct(
        private readonly FilesystemOperator $dataStorage,
        private readonly string $filepath
    ) {}

    public function getContent(): string
    {
        return $this->dataStorage->read($this->filepath);
    }

    public function getStream(): iterable
    {
        return [$this->getContent()];
    }
}
