<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Filesystem;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Filesystem\FlysystemMediaSource;
use Qti3\Package\Model\FileContent\FlysystemFileContent;

class FlysystemMediaSourceTest extends TestCase
{
    private FlysystemMediaSource $mediaSource;

    protected function setUp(): void
    {
        parent::setUp();

        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $filesystem->write('logo.svg', '<svg/>');
        $filesystem->write('img/nested.png', 'png-bytes');

        $this->mediaSource = new FlysystemMediaSource($filesystem);
    }

    #[Test]
    public function itFindsAFileByPackageRelativePath(): void
    {
        $this->assertTrue($this->mediaSource->hasFile('logo.svg'));
        $this->assertTrue($this->mediaSource->hasFile('img/nested.png'));
    }

    #[Test]
    public function itDoesNotFindAMissingFile(): void
    {
        $this->assertFalse($this->mediaSource->hasFile('missing.svg'));
    }

    #[Test]
    public function itAnswersATraversalAttemptWithNoInsteadOfAnException(): void
    {
        $this->assertFalse($this->mediaSource->hasFile('../outside/secret.txt'));
        $this->assertFalse($this->mediaSource->hasFile('img/../../secret.txt'));
    }

    #[Test]
    public function itServesTheFileContent(): void
    {
        $content = $this->mediaSource->getFileContent('logo.svg');

        $this->assertInstanceOf(FlysystemFileContent::class, $content);
        $this->assertSame('<svg/>', $content->getContent());
    }
}
