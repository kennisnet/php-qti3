<?php

declare(strict_types=1);

namespace Qti3\Package\Model\FileContent;

use RuntimeException;

/**
 * File content read lazily from a local filesystem path, e.g. assets bundled
 * with this library. Sibling of {@see ExternalFileContent}: the content
 * implementations are the designated place where bytes are actually fetched.
 */
readonly class LocalFileContent implements IFileContent
{
    public function __construct(
        private string $path,
    ) {}

    public function getContent(): string
    {
        $content = file_get_contents($this->path);
        if ($content === false) {
            throw new RuntimeException('Unable to read file: ' . $this->path);
        }

        return $content;
    }

    public function getStream(): iterable
    {
        return [$this->getContent()];
    }
}
