<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\FileContent;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\ExternalFileContent;
use App\SharedKernel\Infrastructure\Filesystem\ResourceDownloader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExternalFileContentTest extends TestCase
{
    #[Test]
    public function getContentsReturnsDownloadedContent(): void
    {
        $resourceDownloader = $this->createMock(ResourceDownloader::class);
        $resourceDownloader->expects($this->once())
            ->method('downloadFileToStream')
            ->with('https://example.com/file.txt')
            ->willReturn([
                'a', 'b', 'c',
            ]);

        $externalFileContent = new ExternalFileContent(
            'https://example.com/file.txt',
            $resourceDownloader
        );

        $this->assertEquals('abc', $externalFileContent->getContent());
    }
}
