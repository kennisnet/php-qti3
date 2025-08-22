<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\FileContent;

use App\SharedKernel\Domain\Qti\Package\Service\IResourceDownloader;

readonly class ExternalFileContent implements IFileContent
{
    public function __construct(
        public string $url,
        private IResourceDownloader $resourceDownloader,
    ) {}

    public function getContent(): string
    {
        $content = '';
        foreach ($this->getStream() as $chunk) {
            $content .= $chunk;
        }
        return $content;
    }

    public function getStream(): iterable
    {
        return $this->resourceDownloader->downloadFileToStream($this->url);
    }
}
