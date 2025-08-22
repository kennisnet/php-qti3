<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\FileContent;

use RuntimeException;

class LocalFileContent implements IFileContent
{
    public function __construct(
        public readonly string $filepath
    ) {}

    public function getContent(): string
    {
        $content = file_get_contents($this->filepath);

        if ($content === false) {
            throw new RuntimeException('Unable to read file: ' . $this->filepath); // @codeCoverageIgnore
        }

        return $content;
    }

    public function getStream(): iterable
    {
        return [$this->getContent()];
    }
}
