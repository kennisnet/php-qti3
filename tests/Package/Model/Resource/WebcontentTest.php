<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\Resource;

use App\SharedKernel\Domain\Qti\Package\Model\Resource\Webcontent;
use App\SharedKernel\Domain\Qti\Package\Service\IResourceDownloader;
use App\Tests\Unit\SharedKernel\Infrastructure\FilesystemTestCase;
use PHPUnit\Framework\Attributes\Test;

class WebcontentTest extends FilesystemTestCase
{
    private string $filename;
    private string $originalPath;
    private bool $isBinary;
    private IResourceDownloader $resourceDownloader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filename = 'file.xml';
        $this->originalPath = $this->tempDir . '/' . $this->filename;
        $this->isBinary = true;
        $this->resourceDownloader = $this->createMock(IResourceDownloader::class);
    }

    #[Test]
    public function itShouldReturnTheFilename(): void
    {
        $webcontent = new Webcontent(
            'https://example.com/file.xml',
            'ID',
            $this->filename,
            $this->resourceDownloader,
            $this->isBinary,
        );

        $this->assertEquals($this->filename, $webcontent->href);
        $this->assertEquals('https://example.com/file.xml', $webcontent->files->first()->getContent()->url);
    }

    #[Test]
    public function itShouldReturnTheContent(): void
    {
        $str = 'This is a binary file';

        // Write the file content to the file
        file_put_contents($this->originalPath, $str);

        $webcontent = new Webcontent($this->originalPath, 'ID', $this->filename, $this->resourceDownloader, $this->isBinary);

        $this->assertNotEmpty($webcontent->files->first()->getContent());

        // Remove the file
        unlink($this->originalPath);
    }

    #[Test]
    public function itShouldReturnTrueIfTheFileIsBinary(): void
    {
        $resourceFile = new Webcontent('https://example.com/file.xml', 'ID', $this->filename, $this->resourceDownloader, $this->isBinary);

        $this->assertTrue($resourceFile->files->first()->isBinary());
    }
}
