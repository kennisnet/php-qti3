<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\PackageFile;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\XsdFile;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class XsdFileTest extends TestCase
{
    private MemoryFileContent $content;

    protected function setUp(): void
    {
        $this->content = new MemoryFileContent('content');
    }

    #[Test]
    public function aXsdFilenameCanBeGiven(): void
    {
        $xsdFile = new XsdFile('test.xsd', $this->content);

        $this->assertEquals('test.xsd', $xsdFile->getFilepath());
    }

    #[Test]
    public function aNonXsdFilenameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new XsdFile('test.tst', $this->content);
    }
}
