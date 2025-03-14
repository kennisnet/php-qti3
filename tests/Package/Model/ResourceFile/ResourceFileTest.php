<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\ResourceFile;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\IFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\Tests\Unit\SharedKernel\Infrastructure\FilesystemTestCase;
use PHPUnit\Framework\Attributes\Test;

class ResourceFileTest extends FilesystemTestCase
{
    #[Test]
    public function createFileInstance(): void
    {
        $href = 'testfile.xml';
        $contentMock = $this->createMock(IFileContent::class);

        $file = new ResourceFile($href, $contentMock);

        $this->assertInstanceOf(ResourceFile::class, $file);
        $this->assertInstanceOf(PackageFile::class, $file);
        $this->assertSame($href, $file->href);
        $this->assertSame($contentMock, $file->getContent());
    }
}
