<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Model\Resource;

use Qti3\Package\Model\FileContent\ExternalFileContent;
use Qti3\Package\Model\FileContent\MemoryFileContent;
use Qti3\Package\Model\Resource\Webcontent;
use Qti3\Package\Downloader\Resource\IResourceDownloader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WebcontentTest extends TestCase
{
    #[Test]
    public function itShouldReturnTheFilename(): void
    {
        $downloader = $this->createMock(IResourceDownloader::class);

        $webcontent = new Webcontent(
            'https://example.com/file.xml',
            'ID',
            'file.xml',
            new ExternalFileContent('https://example.com/file.xml', $downloader),
        );

        $this->assertEquals('file.xml', $webcontent->href);
        $this->assertEquals('https://example.com/file.xml', $webcontent->files->first()->getContent()->url);
    }

    #[Test]
    public function itShouldReturnTheContent(): void
    {
        $webcontent = new Webcontent('resources/pic.png', 'ID', 'resources/pic.png', new MemoryFileContent('This is a binary file'));

        $this->assertSame('This is a binary file', $webcontent->files->first()->getContent()->getContent());
    }

    #[Test]
    public function itShouldReturnTrueIfTheFileIsBinary(): void
    {
        $webcontent = new Webcontent('resources/pic.png', 'ID', 'resources/pic.png', new MemoryFileContent('x'), true);

        $this->assertTrue($webcontent->files->first()->isBinary());
    }

    #[Test]
    public function itsFileIsMarkedModifiedBecauseItIsAlwaysNewlyAdded(): void
    {
        $webcontent = new Webcontent('resources/pic.png', 'ID', 'resources/pic.png', new MemoryFileContent('x'));

        $this->assertTrue($webcontent->files->first()->isModified());
    }
}
