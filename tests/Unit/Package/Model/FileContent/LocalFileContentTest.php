<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Model\FileContent;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Model\FileContent\LocalFileContent;
use RuntimeException;

final class LocalFileContentTest extends TestCase
{
    #[Test]
    public function itReadsTheFileLazily(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'qti');
        file_put_contents($path, 'file content');

        try {
            $content = new LocalFileContent($path);

            $this->assertSame('file content', $content->getContent());
            $this->assertSame(['file content'], [...$content->getStream()]);
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function itThrowsWhenTheFileCannotBeRead(): void
    {
        $content = new LocalFileContent('/nonexistent/file.css');

        $this->expectException(RuntimeException::class);

        @$content->getContent();
    }
}
